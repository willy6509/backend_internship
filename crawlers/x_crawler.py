import asyncio
import requests
from datetime import datetime, timezone, timedelta
from twikit import Client
import database
import config

LARAVEL_API_URL = "http://127.0.0.1/api/internal/ingest"
API_KEY = config.CREDENTIALS.get("SENTINEL_API_KEY", "BhayangkaraJateng2026Secure!")
WIB = timezone(timedelta(hours=7))

JATENG_LOCATIONS = ['jateng', 'semarang', 'solo', 'surakarta', 'magelang', 'purwokerto', 'tegal', 'brebes', 'kendal', 'demak', 'kudus', 'pati', 'klaten', 'boyolali', 'sragen', 'cilacap']

def is_jateng_relevant(text, username):
    combined = f"{text} {username}".lower()
    return any(loc in combined for loc in JATENG_LOCATIONS)

def send_to_laravel(data_dict):
    try:
        r = requests.post(LARAVEL_API_URL, json=data_dict, headers={"x-api-key": API_KEY}, timeout=10)
        if r.status_code == 201:
            print(f"   [✅] Tersimpan: {data_dict['url']}")
        elif r.status_code == 200:
            print(f"   [⏩] Duplikat: {data_dict['url']}")
    except Exception as e:
        pass

async def main():
    print(f"\n{'='*50}\n🤖 SENTINEL X CRAWLER - TWIKIT FINAL\n{'='*50}")
    print(f"🚀 [{datetime.now(WIB).strftime('%H:%M:%S WIB')}] FASE 1: Menghubungkan ke X...")
    
    client = Client('id')
    
    # Memasukkan cookies dari config.py milikmu
    client.set_cookies({
        'auth_token': config.CREDENTIALS.get("X_AUTH_TOKEN", ""),
        'ct0': config.CREDENTIALS.get("X_CT0", "")
    })
    
    try:
        query = "semarang OR jateng OR polisi OR begal OR klitih OR lantas -filter:retweets"
        tweets = await client.search_tweet(query, product='Latest')
        count = 0
        
        for tweet in tweets:
            if count >= 10: break
            if database.is_exists(tweet.id): continue
            
            content = tweet.full_text if hasattr(tweet, 'full_text') else tweet.text
            if not is_jateng_relevant(content, tweet.user.screen_name): continue
            
            # Waktu WIB
            dt = tweet.created_at if hasattr(tweet, 'created_at') else datetime.now(WIB)
            if isinstance(dt, str):
                try:
                    dt = datetime.strptime(dt, '%a %b %d %H:%M:%S %z %Y')
                except:
                    dt = datetime.now(timezone.utc)
            
            pdata = {
                'type': 'post',
                'username': tweet.user.screen_name,
                'posted_at': dt.astimezone(WIB).strftime('%Y-%m-%d %H:%M:%S'),
                'content': content,
                'url': f"https://x.com/{tweet.user.screen_name}/status/{tweet.id}"
            }
            
            send_to_laravel(pdata)
            database.save_post({'id': tweet.id, 'platform': 'X', 'username': tweet.user.screen_name, 'status': 0})
            count += 1
            
        print(f"   📊 Selesai! Berhasil memproses {count} data real-time.")
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    asyncio.run(main())
