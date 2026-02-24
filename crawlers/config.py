# --- KREDENSIAL X (TWITTER) ---
CREDENTIALS = {
    "X_AUTH_TOKEN": "0bfcbe23cd4d69065d530023b6addee49da383b5", # Ganti dengan token asli
    "X_CT0": "a150c0f1508499900dd49c5ee90f9439966322b238a35ac59cd6b9d4701e89708b048778c898b13caa2bd777f4a53d4e3eff1cecf7da99374833cb8b57cd5f35572a9b20d37f384df1d59192d84c5511"                # Ganti dengan ct0 asli
}

# --- PENGATURAN MESIN CRAWLER ---
SETTINGS = {
    "DELAY_MIN": 2,               # Jeda minimum antar request (detik)
    "DELAY_MAX": 5,               # Jeda maksimum antar request (detik)
    "MAX_POSTS_PER_RUN": 250,      # Maksimal postingan utama yang diambil per putaran
    "MAX_REPLIES_PROCESS": 50      # Maksimal postingan lama yang mau dicek komentarnya
}

# --- KATA KUNCI PENCARIAN ---
KEYWORDS = {
    "X": (
        '( ' # <-- Kurung buka besar untuk membungkus semua query
        
        '("polisi" OR "isilop" OR "plokis" OR "polkis" OR "partai coklat" OR '
        '"wereng" OR "seragam coklat" OR "halo dek" OR "oknum" OR "aparat" OR '
        '"polda" OR "polres" OR "polresta" OR "polrestabes" OR "polsek" OR "bhabin") '
        '("jateng" OR "jawa tengah" OR "semarang" OR "solo" OR "surakarta" OR '
        '"magelang" OR "banyumas" OR "purwokerto" OR "tegal" OR "pekalongan" OR '
        '"brebes" OR "kendal" OR "demak" OR "kudus" OR "pati" OR "klaten" OR '
        '"boyolali" OR "sragen" OR "cilacap" OR "pantura" OR "ngapak") '
        
        'OR ' 
        
        '("kreak" OR "gangster" OR "klitih" OR "begal" OR "tawuran" OR "sajam" OR '
        '"curanmor" OR "judi online" OR "judol" OR "ciu" OR "balap liar" OR '
        '"knalpot brong" OR "pungli" OR "debt collector" OR "dc") '
        '("semarang" OR "solo" OR "jateng" OR "klaten" OR "boyolali" OR "banyumas" OR "pantura") '
        
        'OR ' 
        
        '("percuma lapor polisi" OR "no viral no justice" OR "uang damai" OR '
        '"tilang damai" OR "laporan mangkrak" OR "laporan dicuekin" OR "bekingan" OR '
        '"backing polisi" OR "hukum tajam ke bawah" OR "oknum arogan") '
        
        'OR ' 
        
        '(@poldajateng_ OR @polrestabes_smg OR "kapolda jateng" OR "humas polda jateng" OR '
        '"satlantas semarang" OR "etle jateng" OR "tilang semarang" OR "cctv semarang") '
        
        ') -filter:retweets'
    )
}