# Manual API ARKFleet — Integrasi ARKA MineOps

Manual ini menjelaskan cara tim ARKA MineOps mengakses data equipment, HM/KM readings, project, fixed assets, dan depresiasi dari ARKFleet melalui REST API.

## 1. Referensi Cepat

| Endpoint | Method | Deskripsi |
|---|---|---|
| `/equipment` | GET | Daftar equipment (paginated) |
| `/equipment/{id}` | GET | Detail satu equipment |
| `/equipment/{id}/hm-km-readings` | GET | Riwayat pembacaan HM/KM equipment |
| `/projects` | GET | Daftar project (paginated) |
| `/projects/{code}` | GET | Detail satu project berdasarkan code |
| `/fixed-assets` | GET | Daftar fixed asset (paginated) |
| `/fixed-assets/{id}` | GET | Detail fixed asset + depresiasi terkini |
| `/depreciation/runs` | GET | Daftar depreciation run (paginated) |
| `/depreciation/runs/{id}` | GET | Detail depreciation run + entries |
| `/depreciation/entries` | GET | Daftar depreciation entry (paginated) |

## 2. Autentikasi

API menggunakan **Laravel Sanctum** (token-based).

### Cara mendapatkan token

1. Login ke dashboard ARKFleet.
2. Buka **Settings → API Keys** (`/settings/api-keys`).
3. Buat personal access token baru dengan nama yang jelas (contoh: `MineOps Integration`).
4. Salin token segera setelah dibuat — token plain-text hanya ditampilkan sekali.

Token yang dibuat memiliki ability `api:read` (hanya akses baca).

### Header wajib

```http
Authorization: Bearer {token}
Accept: application/json
```

### Rate limit

- **60 request per menit** per user (berdasarkan user ID dari token).
- Jika request tanpa user terautentikasi, limit dihitung per IP.

## 3. Base URL

| Lingkungan | Base URL |
|---|---|
| Production | `https://arkfleet.arka.co.id/api/v1` |
| Development | `http://localhost:8000/api/v1` |

Semua path endpoint di bawah ini relatif terhadap base URL di atas.

---

## 4. Endpoint

### 4.1 GET /equipment

Mengambil daftar equipment beserta relasi `unitModel`, `department`, dan `plantType`.

#### Query Parameters

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `search` | string | — | Cari di `unit_code` atau `description` |
| `is_active` | boolean | — | Filter status aktif (`true`/`false`) |
| `project_code` | string | — | Filter berdasarkan kode project/site |
| `plant_type` | string | — | Filter nama plant type (partial match) |
| `per_page` | int | `20` | Jumlah per halaman (maks. `100`) |

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment?search=E%20071&project_code=BOW01&is_active=true&per_page=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 71,
      "unit_code": "E 071",
      "description": "Excavator Komatsu PC400",
      "serial_no": "KMTPC400A0N123456",
      "project_code": "BOW01",
      "acquisition_cost": "1850000000.00",
      "acquisition_date": "2024-03-15",
      "in_service_date": "2024-04-01",
      "is_active": true,
      "is_rfu": true,
      "unit_model": {
        "id": 12,
        "name": "PC400-8M0"
      },
      "department": {
        "id": 3,
        "department_name": "Plant Operation"
      },
      "plant_type": {
        "id": 1,
        "name": "Excavator"
      }
    },
    {
      "id": 112,
      "unit_code": "T 112",
      "description": "Dump Truck Hino 500",
      "serial_no": "JHFD1J80XXK789012",
      "project_code": "BOW01",
      "acquisition_cost": "675000000.00",
      "acquisition_date": "2023-08-20",
      "in_service_date": "2023-09-01",
      "is_active": true,
      "is_rfu": true,
      "unit_model": {
        "id": 45,
        "name": "FM 260 JD"
      },
      "department": {
        "id": 3,
        "department_name": "Plant Operation"
      },
      "plant_type": {
        "id": 5,
        "name": "Dump Truck"
      }
    }
  ],
  "first_page_url": "https://arkfleet.arka.co.id/api/v1/equipment?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "https://arkfleet.arka.co.id/api/v1/equipment?page=5",
  "next_page_url": "https://arkfleet.arka.co.id/api/v1/equipment?page=2",
  "path": "https://arkfleet.arka.co.id/api/v1/equipment",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 98
}
```

---

### 4.2 GET /equipment/{id}

Mengambil detail satu equipment beserta relasi `unitModel`, `department`, dan `fixedAsset` (hanya `id`, `status`).

Response dibungkus dalam key `data`.

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment/71" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "data": {
    "id": 71,
    "unit_code": "E 071",
    "description": "Excavator Komatsu PC400",
    "serial_no": "KMTPC400A0N123456",
    "chasis_no": null,
    "engine_model": "SAA6D125E-5",
    "machine_no": "61234",
    "nomor_polisi": null,
    "bahan_bakar": "Solar",
    "warna": "Kuning",
    "capacity": "1.90",
    "remarks": "Unit site BOW01 — pit A",
    "project_code": "BOW01",
    "acquisition_cost": "1850000000.00",
    "acquisition_date": "2024-03-15",
    "in_service_date": "2024-04-01",
    "salvage_value": "185000000.00",
    "useful_life_months": 60,
    "is_active": true,
    "is_rfu": true,
    "unit_model": {
      "id": 12,
      "name": "PC400-8M0"
    },
    "department": {
      "id": 3,
      "department_name": "Plant Operation"
    },
    "fixed_asset": {
      "id": 15,
      "equipment_id": 71,
      "status": "active"
    }
  }
}
```

---

### 4.3 GET /equipment/{id}/hm-km-readings

Mengambil riwayat pembacaan Hour Meter (HM) dan/atau Kilometer (KM) untuk satu equipment.

#### Query Parameters

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `reading_type` | string | — | `hm` atau `km` |
| `date_from` | date | — | Batas awal `reading_date` (YYYY-MM-DD) |
| `date_to` | date | — | Batas akhir `reading_date` (YYYY-MM-DD) |
| `per_page` | int | `50` | Jumlah per halaman (maks. `100`) |

#### Schema field penting

| Field | Tipe | Keterangan |
|---|---|---|
| `reading_type` | string | `hm` (jam operasional) atau `km` (jarak tempuh) |
| `reading_value` | decimal:2 | Nilai pembacaan |
| `reading_date` | date | Tanggal pembacaan |
| `source` | string | Asal data, contoh: `manual`, `import` |
| `notes` | string\|null | Catatan tambahan |

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment/71/hm-km-readings?reading_type=hm&date_from=2026-01-01&date_to=2026-06-30&per_page=50" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1842,
      "equipment_id": 71,
      "reading_date": "2026-06-30",
      "reading_type": "hm",
      "reading_value": "12458.50",
      "source": "import",
      "uploaded_by": 4,
      "upload_batch_id": "batch-2026-06-30-a1",
      "notes": "Pembacaan akhir bulan Juni",
      "created_at": "2026-07-01T02:15:00.000000Z",
      "updated_at": "2026-07-01T02:15:00.000000Z"
    },
    {
      "id": 1701,
      "equipment_id": 71,
      "reading_date": "2026-05-31",
      "reading_type": "hm",
      "reading_value": "12102.00",
      "source": "manual",
      "uploaded_by": 4,
      "upload_batch_id": null,
      "notes": null,
      "created_at": "2026-06-01T08:40:00.000000Z",
      "updated_at": "2026-06-01T08:40:00.000000Z"
    }
  ],
  "first_page_url": "https://arkfleet.arka.co.id/api/v1/equipment/71/hm-km-readings?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "https://arkfleet.arka.co.id/api/v1/equipment/71/hm-km-readings?page=1",
  "next_page_url": null,
  "path": "https://arkfleet.arka.co.id/api/v1/equipment/71/hm-km-readings",
  "per_page": 50,
  "prev_page_url": null,
  "to": 2,
  "total": 2
}
```

---

### 4.4 GET /projects

Mengambil daftar project.

#### Query Parameters

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `selectable_only` | boolean | `false` | Hanya project yang selectable |
| `active_only` | boolean | `true` | Hanya project aktif |
| `search` | string | — | Cari di `code` atau `name` |
| `per_page` | int | `50` | Jumlah per halaman (maks. `100`) |

#### Field utama response

`code`, `sap_code`, `name`, `bowheer`, `location`, `is_active`

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/projects?active_only=true&search=BOW&per_page=50" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "code": "BOW01",
      "sap_code": "BOW01",
      "name": "Tambang Bowheer Site 01",
      "bowheer": "PT Bowheer Mining",
      "location": "Kalimantan Timur",
      "description": "Operasi overburden removal site 01",
      "is_active": true,
      "is_selectable": true,
      "synced_at": "2026-07-20T01:00:00.000000Z"
    },
    {
      "id": 2,
      "code": "BOW02",
      "sap_code": "BOW02",
      "name": "Tambang Bowheer Site 02",
      "bowheer": "PT Bowheer Mining",
      "location": "Kalimantan Timur",
      "description": "Operasi coal getting site 02",
      "is_active": true,
      "is_selectable": true,
      "synced_at": "2026-07-20T01:00:00.000000Z"
    }
  ],
  "per_page": 50,
  "total": 2
}
```

---

### 4.5 GET /projects/{code}

Mengambil detail satu project berdasarkan `code`. Response dibungkus dalam key `data`.

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/projects/BOW01" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "data": {
    "id": 1,
    "code": "BOW01",
    "sap_code": "BOW01",
    "name": "Tambang Bowheer Site 01",
    "bowheer": "PT Bowheer Mining",
    "location": "Kalimantan Timur",
    "description": "Operasi overburden removal site 01",
    "is_active": true,
    "is_selectable": true,
    "synced_at": "2026-07-20T01:00:00.000000Z",
    "created_at": "2025-01-10T03:00:00.000000Z",
    "updated_at": "2026-07-20T01:00:00.000000Z"
  }
}
```

---

### 4.6 GET /fixed-assets

Mengambil daftar fixed asset beserta relasi `equipment` (`id`, `unit_code`) dan `assetClass` (`id`, `code`, `name`).

#### Query Parameters

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `status` | string | — | Filter status, contoh: `active`, `disposed` |
| `per_page` | int | `20` | Jumlah per halaman (maks. `100`) |

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/fixed-assets?status=active&per_page=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 15,
      "equipment_id": 71,
      "asset_class_id": 2,
      "acquisition_cost": "1850000000.00",
      "acquisition_date": "2024-03-15",
      "in_service_date": "2024-04-01",
      "salvage_value": "185000000.00",
      "status": "active",
      "book_method": "SL",
      "book_useful_life_months": 60,
      "tax_method": "DB",
      "tax_useful_life_months": 48,
      "equipment": {
        "id": 71,
        "unit_code": "E 071"
      },
      "asset_class": {
        "id": 2,
        "code": "MP-K2",
        "name": "Heavy Equipment Class K2"
      }
    },
    {
      "id": 22,
      "equipment_id": 209,
      "asset_class_id": 2,
      "acquisition_cost": "3200000000.00",
      "acquisition_date": "2024-11-01",
      "in_service_date": "2024-11-15",
      "salvage_value": "320000000.00",
      "status": "active",
      "book_method": "SL",
      "book_useful_life_months": 72,
      "tax_method": "DB",
      "tax_useful_life_months": 48,
      "equipment": {
        "id": 209,
        "unit_code": "ADT 009"
      },
      "asset_class": {
        "id": 2,
        "code": "MP-K2",
        "name": "Heavy Equipment Class K2"
      }
    }
  ],
  "per_page": 20,
  "total": 45
}
```

---

### 4.7 GET /fixed-assets/{id}

Mengambil detail fixed asset beserta `equipment`, `assetClass`, dan **24 depreciation entries terakhir** (urut `period_date` descending).

Response dibungkus dalam key `data`.

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/fixed-assets/15" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "data": {
    "id": 15,
    "equipment_id": 71,
    "asset_class_id": 2,
    "acquisition_cost": "1850000000.00",
    "acquisition_date": "2024-03-15",
    "in_service_date": "2024-04-01",
    "salvage_value": "185000000.00",
    "status": "active",
    "book_method": "SL",
    "book_useful_life_months": 60,
    "book_residual_rate": "0.1000",
    "tax_method": "DB",
    "tax_useful_life_months": 48,
    "tax_rate": "0.2500",
    "equipment": {
      "id": 71,
      "unit_code": "E 071",
      "description": "Excavator Komatsu PC400"
    },
    "asset_class": {
      "id": 2,
      "code": "MP-K2",
      "name": "Heavy Equipment Class K2"
    },
    "depreciation_entries": [
      {
        "id": 902,
        "depreciation_run_id": 18,
        "fixed_asset_id": 15,
        "book_type": "book",
        "period_date": "2026-06-30",
        "opening_nbv": "1295000000.00",
        "depreciation_amount": "27750000.00",
        "accumulated_depreciation": "582500000.00",
        "closing_nbv": "1267500000.00"
      },
      {
        "id": 901,
        "depreciation_run_id": 18,
        "fixed_asset_id": 15,
        "book_type": "tax",
        "period_date": "2026-06-30",
        "opening_nbv": "980000000.00",
        "depreciation_amount": "38541666.67",
        "accumulated_depreciation": "908541666.67",
        "closing_nbv": "941458333.33"
      }
    ]
  }
}
```

---

### 4.8 GET /depreciation/runs

Mengambil daftar depreciation run beserta relasi `runner` (`id`, `name`).

#### Query Parameters

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `year` | int | — | Filter `period_year` |
| `month` | int | — | Filter `period_month` (1–12) |
| `per_page` | int | `20` | Jumlah per halaman (maks. `100`) |

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/depreciation/runs?year=2026&month=6&per_page=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 18,
      "period_year": 2026,
      "period_month": 6,
      "book_scope": "both",
      "status": "confirmed",
      "total_book_depreciation": "2458000000.00",
      "total_tax_depreciation": "3124500000.00",
      "entry_count": 180,
      "run_by": 2,
      "confirmed_at": "2026-07-02T04:30:00.000000Z",
      "posted_at": null,
      "notes": "Run depresiasi Juni 2026",
      "runner": {
        "id": 2,
        "name": "Finance Admin"
      }
    }
  ],
  "per_page": 20,
  "total": 1
}
```

---

### 4.9 GET /depreciation/runs/{id}

Mengambil detail depreciation run beserta `entries` (termasuk `fixedAsset.equipment`) dan `runner`.

Response dibungkus dalam key `data`.

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/depreciation/runs/18" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "data": {
    "id": 18,
    "period_year": 2026,
    "period_month": 6,
    "book_scope": "both",
    "status": "confirmed",
    "total_book_depreciation": "2458000000.00",
    "total_tax_depreciation": "3124500000.00",
    "entry_count": 180,
    "run_by": 2,
    "confirmed_at": "2026-07-02T04:30:00.000000Z",
    "posted_at": null,
    "notes": "Run depresiasi Juni 2026",
    "runner": {
      "id": 2,
      "name": "Finance Admin"
    },
    "entries": [
      {
        "id": 902,
        "depreciation_run_id": 18,
        "fixed_asset_id": 15,
        "book_type": "book",
        "period_date": "2026-06-30",
        "opening_nbv": "1295000000.00",
        "depreciation_amount": "27750000.00",
        "accumulated_depreciation": "582500000.00",
        "closing_nbv": "1267500000.00",
        "fixed_asset": {
          "id": 15,
          "equipment_id": 71,
          "status": "active",
          "equipment": {
            "id": 71,
            "unit_code": "E 071"
          }
        }
      },
      {
        "id": 910,
        "depreciation_run_id": 18,
        "fixed_asset_id": 28,
        "book_type": "book",
        "period_date": "2026-06-30",
        "opening_nbv": "980000000.00",
        "depreciation_amount": "14000000.00",
        "accumulated_depreciation": "420000000.00",
        "closing_nbv": "966000000.00",
        "fixed_asset": {
          "id": 28,
          "equipment_id": 240,
          "status": "active",
          "equipment": {
            "id": 240,
            "unit_code": "DZ 040"
          }
        }
      }
    ]
  }
}
```

---

### 4.10 GET /depreciation/entries

Mengambil daftar depreciation entry beserta relasi `fixedAsset.equipment`.

#### Query Parameters

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `book_type` | string | — | Filter tipe buku, contoh: `book`, `tax` |
| `fixed_asset_id` | int | — | Filter berdasarkan ID fixed asset |
| `period_from` | date | — | Batas awal `period_date` (YYYY-MM-DD) |
| `period_to` | date | — | Batas akhir `period_date` (YYYY-MM-DD) |
| `per_page` | int | `50` | Jumlah per halaman (maks. `100`) |

#### Contoh request

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/depreciation/entries?book_type=book&fixed_asset_id=15&period_from=2026-01-01&period_to=2026-06-30&per_page=50" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Contoh response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 902,
      "depreciation_run_id": 18,
      "fixed_asset_id": 15,
      "book_type": "book",
      "period_date": "2026-06-30",
      "opening_nbv": "1295000000.00",
      "depreciation_amount": "27750000.00",
      "accumulated_depreciation": "582500000.00",
      "closing_nbv": "1267500000.00",
      "fixed_asset": {
        "id": 15,
        "equipment_id": 71,
        "equipment": {
          "id": 71,
          "unit_code": "E 071"
        }
      }
    },
    {
      "id": 850,
      "depreciation_run_id": 17,
      "fixed_asset_id": 15,
      "book_type": "book",
      "period_date": "2026-05-31",
      "opening_nbv": "1322750000.00",
      "depreciation_amount": "27750000.00",
      "accumulated_depreciation": "554750000.00",
      "closing_nbv": "1295000000.00",
      "fixed_asset": {
        "id": 15,
        "equipment_id": 71,
        "equipment": {
          "id": 71,
          "unit_code": "E 071"
        }
      }
    }
  ],
  "per_page": 50,
  "total": 6
}
```

---

## 5. Contoh Error Response

### 401 Unauthorized

Token hilang, invalid, atau tidak memiliki ability `api:read`.

```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found

Resource tidak ditemukan (ID/code salah atau sudah dihapus).

```json
{
  "message": "No query results for model [App\\Models\\Equipment] 99999"
}
```

### 422 Unprocessable Entity

Validasi gagal (jika berlaku). Format:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": [
      "The per page field must be an integer."
    ]
  }
}
```

### 429 Too Many Requests

Melebihi rate limit 60 request/menit.

```json
{
  "message": "Too Many Attempts."
}
```

Header respons biasanya menyertakan informasi retry, misalnya `Retry-After`.

---

## 6. Panduan Khusus MineOps

### Multi-site dengan `project_code`

Tim MineOps yang mengelola beberapa site tambang dapat memfilter equipment per site menggunakan query parameter `project_code`.

Contoh:

```bash
# Equipment di site BOW01 saja
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment?project_code=BOW01&is_active=true" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Equipment di site BOW02 saja
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment?project_code=BOW02&is_active=true" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

Pola integrasi yang disarankan:

1. Ambil daftar project aktif via `GET /projects`.
2. Untuk setiap site yang dikelola MineOps, panggil `GET /equipment?project_code={code}`.
3. Simpan/mapping `project_code` di sistem MineOps sebagai identitas site.

Unit code yang umum muncul di site tambang (contoh):

| Prefix | Jenis | Contoh |
|---|---|---|
| `E` | Excavator | `E 071`, `E 072` |
| `T` | Truck | `T 112`, `T 113` |
| `ADT` | Articulated Dump Truck | `ADT 009`, `ADT 010` |
| `DZ` | Dozer | `DZ 040`, `DZ 041` |
| `LV` | Light Vehicle | `LV 001` |

### HM/KM Readings

Endpoint `GET /equipment/{id}/hm-km-readings` menyediakan histori pembacaan operasional unit.

| `reading_type` | Makna | Digunakan untuk |
|---|---|---|
| `hm` | Hour Meter — jam operasional | Heavy equipment (excavator, dozer, ADT, dll.) |
| `km` | Kilometer — jarak tempuh | Light vehicles |

Catatan penting:

- `reading_value` bertipe **decimal:2** (contoh: `12458.50`).
- Filter tipe: `?reading_type=hm` atau `?reading_type=km`.
- Filter rentang tanggal: `?date_from=2026-01-01&date_to=2026-06-30`.
- `source` menunjukkan asal data, misalnya `manual` (input dashboard) atau `import` (upload batch).

Contoh gabungan filter untuk excavator `E 071` (asumsikan `id = 71`):

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment/71/hm-km-readings?reading_type=hm&date_from=2026-01-01&date_to=2026-06-30" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

Contoh untuk light vehicle `LV 001` (asumsikan `id = 501`):

```bash
curl -X GET "https://arkfleet.arka.co.id/api/v1/equipment/501/hm-km-readings?reading_type=km&date_from=2026-01-01&date_to=2026-06-30" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Alur integrasi yang disarankan

1. **Autentikasi** — buat token di `/settings/api-keys`, simpan aman di vault/env MineOps.
2. **Master site** — sync `GET /projects` secara berkala.
3. **Fleet per site** — sync `GET /equipment?project_code=...&is_active=true`.
4. **Utilisasi** — sync HM/KM via `GET /equipment/{id}/hm-km-readings` dengan window tanggal.
5. **Aset & depresiasi** (opsional finance) — `GET /fixed-assets` dan `GET /depreciation/entries` untuk rekonsiliasi nilai buku.

---

## Lampiran: Pagination

Endpoint list memakai pagination standar Laravel. Field umum:

| Field | Keterangan |
|---|---|
| `data` | Array record halaman saat ini |
| `current_page` | Halaman aktif |
| `per_page` | Ukuran halaman |
| `total` | Total record |
| `last_page` | Halaman terakhir |
| `next_page_url` | URL halaman berikutnya (`null` jika habis) |
| `prev_page_url` | URL halaman sebelumnya |

Pindah halaman dengan query `?page=2` (atau ikuti `next_page_url`).
