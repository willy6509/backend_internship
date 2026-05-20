# --- KREDENSIAL ---
CREDENTIALS = {
    "X_AUTH_TOKEN": "0bfcbe23cd4d69065d530023b6addee49da383b5",
    "X_CT0": "a150c0f1508499900dd49c5ee90f9439966322b238a35ac59cd6b9d4701e89708b048778c898b13caa2bd777f4a53d4e3eff1cecf7da99374833cb8b57cd5f35572a9b20d37f384df1d59192d84c5511",
    "SENTINEL_API_KEY": "BhayangkaraJateng2026Secure!"
}

# --- PENGATURAN MESIN ---
SETTINGS = {
    "DELAY_MIN": 3,
    "DELAY_MAX": 7,
    "MAX_POSTS_PER_RUN": 100,
    "MAX_REPLIES_PROCESS": 30
}

# --- KATA KUNCI (Slang + Bahasa Gaul 2024-2025) ---
KEYWORDS = {
    "X": (
        '('

        # Institusi Kepolisian + Slang
        '("polisi" OR "isilop" OR "plokis" OR "polkis" OR "polis1" OR '
        '"p0lisi" OR "partai coklat" OR "seragam coklat" OR "wereng coklat" OR '
        '"oknum" OR "aparat" OR "polda" OR "polres" OR "polsek" OR "bhabin" OR '
        '"satlantas" OR "intel" OR "densus" OR "brimob" OR "reskrim") '

        # Wilayah Jawa Tengah
        '("jateng" OR "jawa tengah" OR "semarang" OR "solo" OR "surakarta" OR '
        '"magelang" OR "banyumas" OR "purwokerto" OR "tegal" OR "pekalongan" OR '
        '"brebes" OR "kendal" OR "demak" OR "kudus" OR "pati" OR "klaten" OR '
        '"boyolali" OR "sragen" OR "cilacap" OR "salatiga" OR "wonogiri" OR '
        '"purworejo" OR "wonosobo" OR "temanggung" OR "pantura" OR "ngapak") '

        'OR '

        # Kejahatan Jalanan + Slang
        '("klitih" OR "kl1tih" OR "begal" OR "b3gal" OR "jambret" OR '
        '"curanmor" OR "maling" OR "rampok" OR "bek asu" OR "geng motor" OR '
        '"tawuran" OR "tauran" OR "perang sarung" OR "sajam" OR "celurit" OR '
        '"badik" OR "keroyok" OR "keroyokan" OR "bonyok" OR "babak belur") '
        '("semarang" OR "solo" OR "jateng" OR "klaten" OR "boyolali" OR "cilacap") '

        'OR '

        # Judi + Narkoba Slang
        '("judol" OR "judi online" OR "slot" OR "gacor hari ini" OR '
        '"togel" OR "toge1" OR "sabu" OR "shabu" OR "s4bu" OR "narkoba" OR '
        '"narkotik" OR "barang" OR "pil koplo" OR "dextro" OR "tramadol" OR '
        '"pesta putih" OR "meth" OR "ganja" OR "cimeng" OR "gelek") '

        'OR '

        # Kritik Pelayanan + Viral Slang
        '("no viral no justice" OR "percuma lapor polisi" OR "uang damai" OR '
        '"damai aja" OR "tilang damai" OR "laporan mangkrak" OR "laporan dicuekin" OR '
        '"backing polisi" OR "bekingan" OR "oknum arogan" OR "polisi arogan" OR '
        '"ditilang" OR "kena razia" OR "razia" OR "apes ketilang" OR '
        '"pungli" OR "pungutan liar" OR "minta jatah" OR "sogok" OR "suap") '

        'OR '

        # Akun Resmi & Hashtag
        '(@poldajateng_ OR @polrestabes_smg OR @satlantas_semarang OR '
        '"kapolda jateng" OR "humas polda" OR "etle jateng" OR '
        '#KamtibmasJateng OR #SemarangAman OR #SoloAman) '

        ') -filter:retweets lang:id'
    )
}
