import sqlite3
import os

# Database lokal akan dibuat otomatis di folder yang sama
DB_FILE = os.path.join(os.path.dirname(__file__), 'local_crawler_state.db')

def get_connection():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    """Membuat tabel lokal jika belum ada"""
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS local_posts (
            id TEXT PRIMARY KEY,
            platform TEXT,
            username TEXT,
            status INTEGER DEFAULT 0
        )
    ''')
    conn.commit()
    conn.close()

def is_exists(post_id):
    """Cek apakah ID sudah pernah dicrawl"""
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT 1 FROM local_posts WHERE id = ?", (str(post_id),))
    result = cursor.fetchone()
    conn.close()
    return result is not None

def save_post(data):
    """Simpan ID postingan untuk antrean komentar"""
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute('''
        INSERT OR IGNORE INTO local_posts (id, platform, username, status)
        VALUES (?, ?, ?, ?)
    ''', (str(data.get('id')), data.get('platform', 'X'), data.get('username', ''), data.get('status', 0)))
    conn.commit()
    conn.close()

def get_pending_posts(limit=10):
    """Ambil postingan yang statusnya 0 (belum diambil komentarnya)"""
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM local_posts WHERE status = 0 LIMIT ?", (limit,))
    rows = cursor.fetchall()
    conn.close()
    return [dict(row) for row in rows]

def mark_as_done(post_id):
    """Ubah status jadi 1 jika komentarnya sudah habis dicrawl"""
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE local_posts SET status = 1 WHERE id = ?", (str(post_id),))
    conn.commit()
    conn.close()

# Otomatis inisialisasi tabel saat file ini dipanggil
init_db()