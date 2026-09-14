# Smoke recetas Odoo (local vs público)

Mismos POST que Odoo (parámetro `traldisporta.url.webservice`), contra:

- `local`: `http://127.0.0.1:8080/restapi/v1` (`restapi_prod` Docker)
- `public`: `https://api-traldisporta.com/api/v1` (proxy)

Compara forma (status / JSON / error / claves de fila). No compara recuentos ni importes.

No llama `send_report_morosos`, `newUser`, `newPassword`, `refreshShowvehiclesCache`.

Headers: `Authorization` + `apikey`/`Apikey` + `Checksum` (Odoo maestros usan Authorization; KPI usa apikey). Bodies de maestros: `{"token":""}`; agenda `type=ALL`.

Fechas por defecto: `2024-06-01` / `2024-06-02` y KPI 2024-06 (seed Docker).

```bash
python3 test_shape.py
API_KEY=... python3 prod_contract.py --compare-envs
```

Desde la raíz: `make api-client-smoke-envs` (requiere `API_KEY` y `make api-up`).
