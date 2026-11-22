/*
 * ==============================================================================
 * Smart RW - Reader RFID RC522 Standalone (Serial Monitor + WiFi + API)
 * ==============================================================================
 *
 * Sketch ini adalah versi RINGAN & PRAKTIS untuk menghubungkan RC522 ke laptop:
 * - HANYA butuh library "MFRC522" (tanpa LCD & tanpa Servo).
 * - Mendukung otomatis Arduino Nano ESP32 maupun ESP32 DevKit standar.
 * - Hasil tap kartu, status registrasi, absensi, dan verifikasi KTP langsung
 *   tampil lengkap dan jelas di Serial Monitor (Baudrate 115200).
 *
 * ------------------------------------------------------------------------------
 * WIRING MODUL RC522 KE BOARD
 * ------------------------------------------------------------------------------
 * 1. Jika memakai ARDUINO NANO ESP32:
 *    RC522 Pin  | Pin Arduino Nano ESP32
 *    -----------|---------------------------------------------------------------
 *    VCC (3.3V) | 3V3  ⚠️ (WAJIB 3.3V! JANGAN ke 5V atau VBUS!)
 *    RST        | D9   (GPIO 18)
 *    GND        | GND
 *    MISO       | D12  (GPIO 47)
 *    MOSI       | D11  (GPIO 38)
 *    SCK        | D13  (GPIO 48)
 *    SDA (SS)   | D10  (GPIO 21)
 *
 * 2. Jika memakai ESP32 DEVKIT STANDAR (30/38 Pin):
 *    RC522 Pin  | Pin ESP32 DevKit
 *    -----------|---------------------------------------------------------------
 *    VCC (3.3V) | 3.3V ⚠️ (WAJIB 3.3V! JANGAN ke VIN / 5V!)
 *    RST        | GPIO 22 (atau GPIO 27 jika GPIO 22 dipakai)
 *    GND        | GND
 *    MISO       | GPIO 19
 *    MOSI       | GPIO 23
 *    SCK        | GPIO 18
 *    SDA (SS)   | GPIO 5
 *
 * Library yang dibutuhkan di Arduino IDE (Tools -> Manage Libraries):
 * - "MFRC522" by GithubCommunity
 * ==============================================================================
 */

#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <SPI.h>
#include <MFRC522.h>

// ======================== 1. PENGATURAN WIFI & SERVER ========================
// CUKUP ISI NAMA & PASSWORD WIFI / HOTSPOT HP ANDA DI BAWAH INI:
const char* WIFI_SSID = "NAMA_WIFI_ANDA";
const char* WIFI_PASS = "PASSWORD_WIFI_ANDA";

// URL Endpoint API Smart RW:
// - Jika server online/domain: "https://smartos-rw.unique-monsters.my.id/api/rfid/tap"
// - Jika uji coba lokal Laragon di laptop: "http://192.168.1.xxx:8000/api/rfid/tap" (ganti dengan IP laptop Anda)
const char* API_URL = "https://smartos-rw.unique-monsters.my.id/api/rfid/tap";

// ⚠️ TIDAK PERLU COPY TOKEN SESI DARI WEB!
// Sistem kini memakai Kunci Perangkat Otomatis ("smartrw-rfid-device").
// Server Smart RW akan otomatis mendeteksi sesi absensi, verifikasi e-KTP warga,
// atau pendaftaran koperasi yang sedang aktif tanpa perlu copy token lagi.
const char* DEVICE_AUTH_KEY = "smartrw-rfid-device";

// ======================== 2. KONFIGURASI PIN OTOMATIS ========================
#if defined(ARDUINO_NANO_ESP32)
  #define BOARD_NAME "Arduino Nano ESP32"
  constexpr uint8_t PIN_SS   = D10; // GPIO 21
  constexpr uint8_t PIN_RST  = D9;  // GPIO 18
  constexpr uint8_t PIN_SCK  = D13; // GPIO 48
  constexpr uint8_t PIN_MOSI = D11; // GPIO 38
  constexpr uint8_t PIN_MISO = D12; // GPIO 47
#else
  #define BOARD_NAME "ESP32 DevKit Standar"
  constexpr uint8_t PIN_SS   = 5;
  constexpr uint8_t PIN_RST  = 22; // Ubah ke 27 jika RST Anda dipasang di pin 27
  constexpr uint8_t PIN_SCK  = 18;
  constexpr uint8_t PIN_MOSI = 23;
  constexpr uint8_t PIN_MISO = 19;
#endif

// Cooldown agar 1 kali tap tidak terkirim berkali-kali
constexpr unsigned long SAME_CARD_COOLDOWN_MS = 2000;
constexpr unsigned long ANY_CARD_COOLDOWN_MS  = 800;

MFRC522 rfid(PIN_SS, PIN_RST);
String lastUid = "";
unsigned long lastTapMs = 0;

// Root CA Let's Encrypt untuk HTTPS
const char* ISRG_ROOT_X1 = R"CERT(
-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF
+0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX
6UA5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq
+sWT8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR
3qyHB5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j
3x+UCB5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b
0SHzUvKBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH
0ahmbWnOlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xD
k3SzynTnjh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaH
WBfEbwrbwqHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ1
3hef4Y53CIrU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQE
AwIBBjAPBgNVHRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY
9umbbjANBgkqhkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu
+ILlaS/V9lZLubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+
GnY+EgPbk6ZGQ3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X
/qkSsisRcOj/KKNFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCv
jHIInNzktHCgKQ5ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNb
OhdjsnvzqvHu7UrTkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nx
e5AW0wdeRlN8NwdCjNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1
NbdWhscdCb+ZAJzVcoyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1l
vh+wjChP4kqKOJ2qxq4RgqsahDYVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0
GE44Za4rF2LN9d11TPAmRGunUHBcnWEvgJBQl9nJEiU0Zsnvgc/ubhPgXRR4Xq3
7Z0j4r7g1SgEEzwxA57demyPxgcYxn/eR44/KJ4EBs+lVDR3veyJm+kXQ99b21/
+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----
)CERT";

// ======================== 3. FUNGSI KIRIM TAP KE SERVER ======================
void kirimTapKeServer(const String& uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println(F("[ERROR] WiFi terputus! Mencoba menghubungkan ulang..."));
    WiFi.reconnect();
    return;
  }

  Serial.println();
  Serial.print(F("➡️  Mengirim UID ["));
  Serial.print(uid);
  Serial.println(F("] ke server Smart RW..."));

  HTTPClient http;
  http.setTimeout(10000);

  WiFiClient plainClient;
  WiFiClientSecure secureClient;
  bool begun = false;

  String urlStr = String(API_URL);
  if (urlStr.startsWith("https://")) {
    secureClient.setCACert(ISRG_ROOT_X1);
    begun = http.begin(secureClient, urlStr);
  } else {
    begun = http.begin(plainClient, urlStr);
  }

  if (!begun) {
    Serial.println(F("[ERROR] Gagal inisialisasi koneksi HTTP/HTTPS!"));
    return;
  }

  http.addHeader("Accept", "application/json");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + DEVICE_AUTH_KEY);

  String payload = "{\"uid\":\"" + uid + "\"}";
  int httpCode = http.POST(payload);
  String body = (httpCode > 0) ? http.getString() : http.errorToString(httpCode);
  http.end();

  Serial.print(F("HTTP Status: "));
  Serial.println(httpCode);

  if (httpCode == 200) {
    // Parse status respons
    if (body.indexOf("\"present\"") >= 0) {
      Serial.println(F("✅ [SUKSES ABSENSI] Kehadiran warga berhasil dicatat di kegiatan!"));
    } else if (body.indexOf("\"already_present\"") >= 0) {
      Serial.println(F("ℹ️  [SUDAH ABSEN] Warga ini sudah tercatat hadir sebelumnya."));
    } else if (body.indexOf("\"registered\"") >= 0) {
      Serial.println(F("🎉 [KARTU TERDAFTAR] Kartu RFID berhasil dipasangkan ke warga!"));
      Serial.println(F("   Minta warga tap sekali lagi untuk absensi kehadiran."));
    } else if (body.indexOf("\"ktp_registered\"") >= 0) {
      Serial.println(F("🎉 [E-KTP TERDAFTAR] e-KTP fisik berhasil didaftarkan di RT & sistem web!"));
      Serial.println(F("   Warga kini resmi memiliki hak e-Voting dan verifikasi pinjaman."));
    } else if (body.indexOf("\"ktp_verified\"") >= 0) {
      Serial.println(F("✅ [E-KTP TERVERIFIKASI] Verifikasi fisik e-KTP berhasil!"));
    } else if (body.indexOf("\"ktp_already_verified\"") >= 0) {
      Serial.println(F("ℹ️  [E-KTP SUDAH AKTIF] e-KTP ini sudah terverifikasi sebelumnya."));
    } else if (body.indexOf("\"koperasi_registered\"") >= 0) {
      Serial.println(F("🎉 [KOPERASI] Kartu terdaftar & terverifikasi untuk pendaftaran koperasi!"));
    } else if (body.indexOf("\"koperasi_verified\"") >= 0) {
      Serial.println(F("✅ [KOPERASI] Tap kartu berhasil. Warga dapat lanjut di web koperasi."));
    } else {
      Serial.print(F("✅ Respon OK: "));
      Serial.println(body);
    }
  } else if (httpCode == 401) {
    Serial.println(F("❌ [TOKEN SALAH / KEDALUWARSA]"));
    Serial.println(F("   Buka menu web (Kegiatan / Verifikasi KTP) dan klik 'Buka Sesi',"));
    Serial.println(F("   lalu salin token 64 karakter baru ke variabel SESSION_TOKEN!"));
  } else if (httpCode == 403) {
    Serial.println(F("❌ [AKSES DITOLAK] Akun pembuka sesi tidak memiliki wewenang untuk aksi ini."));
  } else if (httpCode == 404) {
    Serial.println(F("❌ [DATA TIDAK DITEMUKAN] Warga tidak aktif atau berada di luar wilayah."));
  } else if (httpCode == 409) {
    Serial.println(F("⚠️  [JADWAL KEGIATAN] Kegiatan belum dimulai atau sudah ditutup."));
  } else if (httpCode == 422) {
    Serial.print(F("⚠️  [VALIDASI GAGAL]: "));
    Serial.println(body);
    if (body.indexOf("warga lain") >= 0 || body.indexOf("sudah terdaftar") >= 0) {
      Serial.println(F("   ❌ PERINGATAN: Kartu e-KTP ini SUDAH TERDAFTAR di akun warga lain!"));
    } else if (body.indexOf("kedaluwarsa") >= 0) {
      Serial.println(F("   ❌ PERINGATAN: Sesi pendaftaran telah kedaluwarsa. Mulai ulang sesi di website."));
    } else {
      Serial.println(F("   Pastikan di web Anda sudah klik 'Daftarkan Kartu' / 'Aktifkan Tap KTP'."));
    }
  } else if (httpCode == 429) {
    Serial.println(F("⚠️  [TERLALU CEPAT] Tunggu beberapa detik sebelum tap lagi."));
  } else {
    Serial.print(F("❌ [KONEKSI GAGAL] Kode: "));
    Serial.print(httpCode);
    Serial.print(F(" | Info: "));
    Serial.println(body);
  }

  Serial.println(F("--------------------------------------------------"));
  Serial.println(F("Siap membaca kartu berikutnya..."));
}

// ======================== 4. SETUP UTAMA =====================================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println();
  Serial.println(F("=================================================="));
  Serial.println(F("   SMART RW - READER RFID RC522 STANDALONE        "));
  Serial.print(F("   Board Terdeteksi: "));
  Serial.println(F(BOARD_NAME));
  Serial.println(F("=================================================="));

  // 1. Inisialisasi SPI & Modul RC522 TERLEBIH DAHULU (agar langsung tahu status hardware)
  Serial.print(F("1. Inisialisasi modul RFID RC522... "));
  #if defined(ARDUINO_NANO_ESP32)
    SPI.begin(); // Arduino Nano ESP32 default SPI (D13 SCK, D12 MISO, D11 MOSI)
  #else
    SPI.begin(PIN_SCK, PIN_MISO, PIN_MOSI, PIN_SS);
  #endif

  rfid.PCD_Init();
  delay(50);

  byte v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
  #if !defined(ARDUINO_NANO_ESP32)
  // Jika di pin RST 22 belum terbaca, coba otomatis di pin RST 27
  if (v == 0x00 || v == 0xFF) {
    Serial.println();
    Serial.print(F("   RST di pin 22 tidak merespon, mencoba pin RST 27... "));
    rfid = MFRC522(PIN_SS, 27);
    rfid.PCD_Init();
    delay(50);
    v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
  }
  #endif

  if (v == 0x00 || v == 0xFF) {
    Serial.println(F("GAGAL!"));
    Serial.println(F("⚠️  [PERINGATAN] Modul RC522 TIDAK TERDETEKSI (Version: 0x00/0xFF)!"));
    Serial.println(F("   Penyebab paling sering:"));
    Serial.println(F("   1. Kaki pin header di modul RC522 BELUM DISOLDER (hanya ditancap)."));
    Serial.println(F("   2. Kabel jumper longgar / salah pin:"));
    Serial.println(F("      - VCC -> 3.3V (JANGAN 5V!)"));
    Serial.println(F("      - RST -> Pin 22 atau Pin 27"));
    Serial.println(F("      - SDA -> Pin 5 | SCK -> Pin 18 | MOSI -> Pin 23 | MISO -> Pin 19"));
  } else {
    // Set kekuatan antena ke tingkat MAKSIMAL agar pembacaan kartu / e-KTP sangat peka
    rfid.PCD_SetAntennaGain(MFRC522::RxGain_max);
    Serial.print(F("OK! (Firmware Version: 0x"));
    Serial.print(v, HEX);
    Serial.println(F(" | Antena: GAIN MAKSIMAL)"));
  }

  // 2. Hubungkan ke WiFi
  Serial.println();
  Serial.print(F("2. Menghubungkan ke WiFi ["));
  Serial.print(WIFI_SSID);
  Serial.print(F("] "));

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 20) {
    delay(500);
    Serial.print(F("."));
    retry++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(F(" TERHUBUNG!"));
    Serial.print(F("   IP ESP32 : "));
    Serial.println(WiFi.localIP());
    Serial.print(F("   Target API: "));
    Serial.println(API_URL);
  } else {
    Serial.println();
    Serial.println(F("⚠️  WiFi belum tersambung (masih offline)."));
    Serial.println(F("   Jika Anda tap kartu, UID akan tetap ditampilkan di Serial Monitor"));
    Serial.println(F("   tetapi belum terkirim ke web server. Pastikan nama WiFi & Password benar."));
  }

  Serial.println(F("=================================================="));
  Serial.println(F("Status: SIAP! Tempelkan kartu / e-KTP ke sensor..."));
  Serial.println(F("=================================================="));
}

// ======================== 5. LOOP PEMBACAAN KARTU ============================
unsigned long lastReaderCheckMs = 0;

void ensureReaderAlive() {
  byte v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
  if (v == 0x00 || v == 0xFF) {
    Serial.println(F("⚠️  [RFID] Modul RC522 terputus / glitch, melakukan recovery..."));
#if !defined(ARDUINO_NANO_ESP32)
    rfid = MFRC522(PIN_SS, PIN_RST);
#endif
    rfid.PCD_Init();
    delay(20);
    rfid.PCD_SetAntennaGain(MFRC522::RxGain_max);
    rfid.PCD_AntennaOn();
  }
}

void loop() {
  unsigned long now = millis();

  // Watchdog RC522 berkala setiap 3 detik
  if (now - lastReaderCheckMs > 3000) {
    lastReaderCheckMs = now;
    ensureReaderAlive();
  }

  // Cek apakah ada kartu di dekat modul RC522 (REQA / WUPA)
  byte bufferATQA[2];
  byte bufferSize = sizeof(bufferATQA);
  bool cardPresent = rfid.PICC_IsNewCardPresent();
  if (!cardPresent) {
    cardPresent = (rfid.PICC_WakeupA(bufferATQA, &bufferSize) == MFRC522::STATUS_OK);
  }

  if (!cardPresent) {
    delay(20);
    return;
  }

  // Multi-pass retry 3x
  bool cardRead = false;
  for (uint8_t retry = 0; retry < 3; retry++) {
    if (rfid.PICC_ReadCardSerial()) {
      cardRead = true;
      break;
    }
    delay(10);
  }

  if (!cardRead) {
    delay(20);
    return;
  }

  // Validasi panjang UID (4 byte, 7 byte, atau 10 byte)
  uint8_t uidSize = rfid.uid.size;
  if (uidSize != 4 && uidSize != 7 && uidSize != 10) {
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    delay(50);
    return;
  }

  // Konversi byte UID ke string HEX uppercase (contoh: "A1B2C3D4")
  String uid = "";
  uid.reserve(uidSize * 2);
  for (uint8_t i = 0; i < uidSize; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += '0';
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  // Cooldown filter agar tidak spam berkali-kali
  unsigned long nowMs = millis();
  if (uid == lastUid && (nowMs - lastTapMs) < SAME_CARD_COOLDOWN_MS) {
    return;
  }
  if ((nowMs - lastTapMs) < ANY_CARD_COOLDOWN_MS) {
    return;
  }

  lastUid = uid;
  lastTapMs = nowMs;

  Serial.println();
  Serial.println(F("--------------------------------------------------"));
  Serial.print(F("💳 [KARTU TERDETEKSI] UID: "));
  Serial.print(uid);
  Serial.print(F(" (Panjang: "));
  Serial.print(uidSize);
  Serial.println(F(" byte)"));

  // Kirim data tap ke server jika WiFi terhubung
  if (WiFi.status() == WL_CONNECTED) {
    kirimTapKeServer(uid);
  } else {
    Serial.println(F("⚠️  [WIFI OFFLINE] UID terbaca di alat, tetapi TIDAK terkirim ke web"));
    Serial.println(F("   karena WiFi belum tersambung."));
    Serial.println(F("--------------------------------------------------"));
  }
}
