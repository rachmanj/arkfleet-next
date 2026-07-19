# SAP Business One Service Layer — Concise Reference (Still Detailed)

**Source:** *Working with SAP Business One Service Layer* (SAP Business One 9.1–9.3 for SAP HANA, Document v1.15, 2019-12-02)  
**Original file:** `Working_with_SAP_Business_One_Service_Layer.md`  
This note compresses the manual; long examples, screenshots, and repetitive samples are omitted. For full schemas and patch-specific behavior, use the official manual and the API reference shipped with your Service Layer installation.

**Indonesian version:** `Working_with_SAP_Business_One_Service_Layer_RINGKAS.md`

---

## 1. What Is the Service Layer?

- A newer API for SAP Business One data and services over **HTTP/HTTPS** and **OData**, mainly **JSON** payloads (not XML for general entity CRUD).
- Supports **OData v3** and **v4** — choose via path or header:
  - v3: `https://<server>:<port>/b1s/v1`
  - v4: `https://<server>:<port>/b1s/v2` or header `OData-Version: 4.0` on the request (e.g. `GET /$metadata`).
- **DI Core** matches DI API → aligned object/property definitions; easier for developers who know DI API.
- Example clients: WCF (.NET), `data.js` (JavaScript), and other OData libraries.

**Audience:** administrators who install/configure Service Layer and developers building add-ons or integrations.

---

## 2. Requirements & Architecture

| Aspect | Summary |
|--------|---------|
| OS (per manual) | SUSE Linux Enterprise 11 SP2, 64-bit |
| Web server | Apache HTTP Server; load balancer + cluster members |
| Database | SAP HANA Platform Edition (version per your manual edition) |
| Mode | **Integrated** (with HANA on one server) or **distributed** (separate machines for throughput) |
| Core components | **OData Parser** (HTTP → business objects), **DI Core**, **session manager** (sticky to a node), **OBServer** (business logic), multiprocessing |

**Scale-out:** multiple Service Layer instances behind a load balancer; **sticky** sessions to the same node; if a node fails, another node can validate the session from the DB and continue without re-login (see §14).

**Firewall:** balancer ↔ member traffic uses HTTP; restrict so only the balancer can reach members.

---

## 3. Installation (Key Points)

- **No pure remote install:** run the wizard on **each** server (balancer and every member).
- Recommended order: install **members** first (member details are required when installing the balancer).
- File prerequisites: RPM packages (`B1ServerToolsCommon`, `B1ServerToolsJava64`, `B1ServerToolsSupport`, `B1ServiceLayerApacheWebServer`, `B1ServiceLayerComponent`) + `install.bin`; keep folder layout (separate `RPM` folder).
- Run as **root**: `…/Packages.Linux/ServerComponents` → `./install.bin`.
- Select **Service Layer** feature, certificate, HANA connection, balancer options (port, member addresses, **Max Threads** per member), **SLD** (from 9.1 PL06) — SLD address must match for balancer and members when split. **IPv6 is not supported** for that configuration.
- Post-install: service user **B1service0** (do not change permissions casually); **balancer manager** `https://<Balancer>:<Port>/balancer-manager`; docs under `<Installation>/ServiceLayer/doc/`; API reference `https://<Load Balancer>:<Port>/`.
- System DB **SBOCOMMON** and company DB must be at aligned versions. HANA user: at least **SBOCOMMON** — SELECT, INSERT, DELETE, UPDATE, EXECUTE (grantable); company DB — full privileges per guide.
- **Service Layer version:** `/etc/init.d/b1s --version` or `-v` (from 9.1 PL04).
- **SLD:** from 9.1 PL06 login uses SLD; DB credentials come from SLD; address stored in `b1s.conf`.

---

## 4. Service URLs & Terms

| Element | Example / note |
|---------|----------------|
| Service root | `https://<server>:<port>/b1s/<v1|v2>/` |
| Resource | `…/b1s/v1/Items`, `…/b1s/v1/BusinessPartners('c1')` |
| Query | `?$top=2&$orderby=ItemCode` |
| Verbs | GET, POST, PATCH, PUT, DELETE per OData/REST |
| Body | JSON |

---

## 5. Login, Logout, Session

**Login**

```http
POST https://<host>:<port>/b1s/v1/Login
Content-Type: application/json

{"CompanyDB": "US506", "UserName": "manager", "Password": "1234"}
```

Success: `200 OK`, cookies **`B1SESSION`**, **`ROUTEID`** (sticky), body includes `SessionId`.

**Logout:** `POST /Logout` with the same cookies → `204 No Content`.

**Session:** After login, **every** request must send `Cookie: B1SESSION=…; ROUTEID=…`. Otherwise: `401` *Invalid session*. Browsers usually handle cookies automatically; desktop clients must set them manually.

**SSO (from 9.2 for HANA):** SAML2 via SLD — **PAOS** (non-browser) or **HTTP-POST** (browser). PAOS flow: POST `/b1s/v1/ssob1s/` with PAOS header → `JSESSIONID` → IDP exchange → POST `/b1s/v1/ssob1s` with cookie → `B1SESSION` + `ROUTEID`. Service Layer and SLD on the same host is recommended; if separate, **keep time synchronized**.

---

## 6. Metadata & Service Document

- **`GET /$metadata`** — types, entities, **Actions** / FunctionImport (v3).
- Header **`B1S-ExperimentalMetadata: true`** if the metadata subset does not show (experimental; not recommended to leave True in production).
- **UDF/UDT/UDO** in metadata from 9.1 PL05; metadata can differ per company DB. UDT “no object” type as a simple entity.
- **`GET /`** (service document) — list of entity sets (`value`: `name`, `kind`, `url`).

---

## 7. CRUD — Technical Patterns

### Create (POST)

- Success: **`201 Created`** + entity body.
- BP example: `POST /BusinessPartners` with `CardCode`, `CardName`, `CardType` — enum accepts name or value (`"cCustomer"` / `"C"`).
- Documents with lines: `DocumentLines` array of line objects.
- Error: **`4xx`**, body `{"error": {"code": …, "message": {"lang","value"}}}`.

### Read (GET)

- Single entity: `GET /BusinessPartners('c1')` or `(CardCode='c1')`.
- Numeric key: `GET /Orders(22)` — **no** quotes for integers.
- Composite key: `GET /SalesTaxAuthorities(Code='AK',Type=-3)`.
- String keys use **single quotes** `'…'`.

### Update (PATCH vs PUT)

- **PATCH** (recommended): only properties in the body change; others unchanged → success usually **`204 No Content`**.
- **PUT**: replace; omitted properties → default/null.
- **Read-only** properties (e.g. `CardCode`) are ignored silently.

### Delete (DELETE)

- Success: **`204`**. Many document objects **cannot be deleted** via API (manual example: sales order) → `400` *action not supported*.

### Create Without Response Body

```http
POST /b1s/v1/Items
Prefer: return-no-content
```

Success: **`204`**, **`Location`**, **`Preference-Applied: return-no-content`**.

### HTTP Method Override (from 9.1 PL04)

Clients without PATCH:

```http
POST /Orders(1)
X-HTTP-Method-Override: PATCH
```

Also works for PUT, MERGE, DELETE.

---

## 8. Actions

- **Bound to entity:** e.g. `POST /Orders(22)/Close` (check metadata for name and parameters).
- **Global:** e.g. services exposed in metadata as `…Service` / Action (v4) or FunctionImport (v3).
- **Action** is a v4 concept; in v3 the counterpart is **FunctionImport**.
- All use **POST** to the action URL.

---

## 9. Query Options (Core)

The manual covers: `$filter`, `$select`, `$orderby`, `$top`/`$skip`, pagination, aggregation (**sum, average, max, min, count, countdistinct, inlinecount**), **grouping**, **cross-join** with `$expand` / calculation / aggregation, **row-level filter**, enum/datetime/time queries, etc.  
**Limits:** OData arithmetic operators and many string/date/math functions are **not** fully supported (see §13).

**Case-insensitive (9.2 PL07+):** header **`B1S-CaseInsensitive: true`** on the query.

---

## 10. Batch

- Endpoint: **`POST …/$batch`** with **`Content-Type: multipart/mixed`** and boundary; OData v4 uses standard OData batch patterns.
- **Change set** for multiple operations in one batch; cross-request references via **`Content-ID`** (e.g. PATCH `/$1` refers to an entity created in the change set).
- Valid response: **`202 Accepted`** (OData v3) or **`200 OK`** (v4) — each sub-request has a sub-response.
- **OData batch rollback is not supported** (see §13).
- If one operation in a change set fails, response behavior is as described in the manual (e.g. one error response for the failing change set).

---

## 11. Other Features (Feature List)

| Topic | Brief content |
|--------|----------------|
| Individual properties | GET value / raw value (direct access to nested complex-type properties is restricted — see manual) |
| Associations | **NavigationProperty** in metadata (v3); `$expand` for navigation |
| User-defined schema | Schema file under `conf`, header/per-request |
| UDF / UDT / UDO | Metadata + CRUD; UDO can cancel/close; new metadata may require **Service Layer restart** |
| Attachments | Folder, upload (Linux/Windows), download, update |
| Item & employee images | Folder, GET/PUT/DELETE |
| **JavaScript extension** | Engine, URL mapping, SDK (HTTP, CRUD, query, in-script transactions — **≤ 10** operations per transaction), logging, deployment, consumption from .NET |
| **CORS** | `CorsEnable`, `CorsAllowedOrigins`, `CorsAllowedHeaders` (9.2 PL07+); OPTIONS preflight |
| **Ping Pong** (9.3 PL10+) | `https://<host>:<port>/ping/`, `/ping/load-balancer`, `/ping/node`, `/ping/node/1` — direct response from Apache (not B1 core), for network tests/monitoring |

---

## 12. Configuration (`conf/b1s.conf`, JSON)

Changes apply **immediately** after save. Legacy path (9.1 PL00–03): `/ServiceLayer/b1s/modules/b1s.conf`. In a cluster, each member needs a consistent schema/config.

### Server connection

| Option | Short description |
|--------|-------------------|
| `Server` | HANA instance, default `127.0.0.1:30015` |
| `DbUserName` / `DbPassword` | Encrypted unless SYSTEM/sa; from **9.1 PL06** optional (from **SLD** — recommended) |
| `License Server` | Default `127.0.0.1:40000` (9.1 PL05+; shared with SLD) |
| `SessionTimeout` | Minutes, default **30** |

### Other options

| Option | Short description |
|--------|-------------------|
| `ExperimentalMetadata` | Default false; true = fuller metadata (not recommended in production) |
| `WCFCompatible` | true = metadata adjustments for WCF (.NET) |
| `MetadataWithoutSession` | true = metadata after login without strict session (cluster: see FAQ — may need login to a specific **member**) |
| `PageSize` | Default **20** |
| `Schema` | Schema file in `conf` folder (9.1 PL03+) |
| `CorsEnable` / `CorsAllowedOrigins` / `CorsAllowedHeaders` | CORS |

**Per request:** header `B1S-<OptionName>: <value>` (9.1 PL01+), e.g. `B1S-WCFCompatible: True`, `B1S-PageSize: 100`.

---

## 13. Important Limitations

### OData

- OData **1.0 / 2.0** not supported.
- XML for general entity CRUD **not** supported.
- Direct access to **complex-type** sub-properties is restricted (see individual properties chapter).
- **odata=fullmetadata** (v3) / **odata.metadata=full** (v4) not supported.
- **NavigationProperty** in metadata: **v3 only**.
- Query: arithmetic operators and many OData functions **not** implemented yet.

### Versus DI API

- No **RecordSet** / direct SQL.
- No **ImportFromXML** / **ExportFromXML**.
- New UDO/UDF/UDT metadata may require **Service Layer restart**.
- No **multi-request user transactions** like DI API Start/EndTransaction — transactions are internal per request/batch.
- **JSONP** not supported.

---

## 14. High Availability & Benchmark

- Apache MPM + load balancing; sticky sessions; node failover with session validation in the DB.
- Load test (order→delivery→invoice, 20 lines): throughput (tps) depends on HANA cores, Service Layer cores, and concurrent request count; the manual includes tables (e.g. up to ~**6.7 tps** in one configuration with 80 HANA cores). Benefit of adding concurrency tapers after about **40** parallel requests in that test scenario.

---

## 15. FAQ (Selected)

| Question | Short answer |
|----------|--------------|
| SL vs DI API vs DI Server | SL = OData/REST, web-friendly; DI API = COM/Windows; DI Server = SOAP |
| PUT vs PATCH | PUT = full replace; PATCH = delta — generally prefer PATCH |
| Metadata without importing as external web service in Visual Studio | `MetadataWithoutSession` + login to the right node; in cluster → log in to a **member**, then `GET …/$metadata` from that member |
| Non-SYSTEM DB user | Create HANA user; GRANT on **SBOCOMMON** and company schema (see manual for full SQL) |
| Auto-start | Linux services `b1s`, `b1s50000` (balancer), `b1s50001`… — `chkconfig` |
| HANA client not in default path | From 9.1 PL04 supported; earlier symlink or `ld.so.conf` + `ldconfig` |

---

## 16. Appendices (Essence)

- **Appendix I:** How call patterns map for CRUD, company service, transactions, query, UDO vs DI API.
- **Appendix II:** **Collection**, **business object**, and **property** naming differences between Service Layer and DI API.

---

*This concise note does not replace the official Administrator’s Guide or your patch release notes. Verify behavior against your installed SAP Business One version.*
