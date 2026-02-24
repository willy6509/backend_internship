import asyncio
from twikit import Client, TooManyRequests
import database
import config
import random
import time
import requests

LARAVEL_API_URL = "http://127.0.0.1:8000/api/internal/ingest"
API_KEY = "KunciRahasiaPolda2026" # <-- Ini harus SAMA PERSIS dengan di .env Laravel

def send_to_laravel(data_dict):
    """Fungsi untuk menembak API Laravel (Microservices)"""
    headers = {
        "X-API-KEY": API_KEY,
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    try:
        response = requests.post(LARAVEL_API_URL, json=data_dict, headers=headers)
        if response.status_code == 201:
            print(f"   [+] Tersimpan Aman di Blockchain DB: {data_dict['url']}")
        elif response.status_code == 200:
            print(f"   [⏩] Duplikat Diabaikan: {data_dict['url']}")
        else:
            print(f"   [❌] Gagal Kirim ke Laravel: {response.status_code} - {response.text}")
    except Exception as e:
        print(f"   [⚠️] Koneksi ke API Backend Gagal: {e}")

# --- UTILITIES ---
async def random_sleep():
    """Tidur acak agar terlihat seperti manusia"""
    await asyncio.sleep(random.uniform(config.SETTINGS["DELAY_MIN"], config.SETTINGS["DELAY_MAX"]))

def get_client():
    client = Client('en-US')
    client.set_cookies({
        'auth_token': config.CREDENTIALS["X_AUTH_TOKEN"],
        'ct0': config.CREDENTIALS["X_CT0"]
    })
    return client

# --- FASE 1: PANEN POSTINGAN (CEPAT) ---
async def harvest_posts():
    print("🚀 [FASE 1] Mencari Postingan Baru...")
    client = get_client()
    limit = config.SETTINGS["MAX_POSTS_PER_RUN"]
    count = 0
    
    try:
        tweets = await client.search_tweet(config.KEYWORDS["X"], product='Latest')
        
        while tweets and count < limit:
            for tweet in tweets:
                # 1. Cek Duplikat di DB Lokal (Opsional, agar tidak buang resource)
                if database.is_exists(tweet.id):
                    continue
                
                # 2. Format Data sesuai Kontrak API Laravel
                post_url = f"https://x.com/{tweet.user.screen_name}/status/{tweet.id}"
                post_data = {
                    'type': 'post',
                    'username': tweet.user.screen_name,
                    'posted_at': str(tweet.created_at), 
                    'content': tweet.text,
                    'url': post_url,
                }
                
                # 3. Kirim ke Backend Laravel
                send_to_laravel(post_data)
                
                # 4. Simpan ke DB Lokal (Hanya untuk tracking status crawling komentar)
                local_data = {
                    'id': tweet.id,
                    'platform': 'X',
                    'username': tweet.user.screen_name,
                    'status': 0 # 0 = belum dicrawl komentarnya
                }
                database.save_post(local_data)
                
                print(f"   [+] Post Baru: {tweet.user.screen_name}")
                count += 1
            
            # Pindah Halaman
            if count < limit:
                print(f"   ⏳ Next Page... (Total: {count})")
                await random_sleep()
                tweets = await tweets.next()
            else:
                break
                
    except Exception as e:
        print(f"❌ Error Fase 1: {e}")

# --- FASE 2: PROSES KOMENTAR (PENDALAMAN) ---
async def process_comments():
    limit = config.SETTINGS["MAX_REPLIES_PROCESS"]
    print(f"\n🔍 [FASE 2] Mengambil Komentar dari {limit} postingan antrean...")
    
    client = get_client()
    
    # Ambil postingan dari DB lokal yang statusnya masih 0
    pending_posts = database.get_pending_posts(limit=limit)
    
    if not pending_posts:
        print("   ✅ Semua postingan sudah bersih! Tidak ada antrean.")
        return

    for post in pending_posts:
        post_id = post['id'] # Sesuaikan dengan struktur key dari database lokal Anda
        parent_url = f"https://x.com/{post['username']}/status/{post_id}"
        print(f"   Checking: {post['username']} ({post_id})")
        
        try:
            # Wajib jeda per postingan detail
            await random_sleep()
            
            # Ambil Detail Tweet
            tweet = await client.get_tweet_by_id(post_id)
            
            # Ambil Replies
            reply_count = 0
            if hasattr(tweet, 'replies'):
                for reply in tweet.replies:
                    # Pastikan reply valid
                    if not hasattr(reply, 'id'): continue
                    
                    if not database.is_exists(reply.id):
                        # Format Data Reply untuk Laravel
                        reply_data = {
                            'type': 'reply',
                            'username': reply.user.screen_name,
                            'posted_at': str(reply.created_at),
                            'content': reply.text,
                            'url': f"https://x.com/{reply.user.screen_name}/status/{reply.id}",
                            'parent_url': parent_url # PENTING: Untuk relasi di Laravel
                        }
                        
                        # Kirim ke Backend Laravel
                        send_to_laravel(reply_data)
                        
                        # Simpan di DB lokal agar tidak dicrawl ulang
                        database.save_post({'id': reply.id, 'status': 1})
                        reply_count += 1
            
            if reply_count > 0:
                print(f"      └── Dapat {reply_count} komentar.")
            
            # PENTING: Ubah status jadi 1 (Selesai) di Database lokal
            database.mark_as_done(post_id)

        except TooManyRequests:
            print("🛑 RATE LIMIT! Istirahat 10 menit...")
            time.sleep(600)
        except Exception as e:
            print(f"      ⚠️ Gagal: {e}")
            database.mark_as_done(post_id)

async def run():
    # Jalankan berurutan
    await harvest_posts()
    await process_comments()

if __name__ == "__main__":
    asyncio.run(run())