# Postman + Documenter

## Archivos

- `docs/postman/Traldisporta_Client_API.postman_collection.json`
- `docs/postman/Traldisporta_API_Maintenance_Docs.postman_collection.json`
- `docs/postman/Traldisporta_API.postman_environment.json`

## Que archivo usar

- Usa `Traldisporta_API_Maintenance_Docs.postman_collection.json` para publicar documentacion profesional con Postman Documenter.
- Usa `Traldisporta_Client_API.postman_collection.json` como coleccion base historica.
- Usa `Traldisporta_API.postman_environment.json` como entorno sin secretos.

## Importar en Postman

1. Abre Postman.
2. Click en `Import`.
3. Selecciona:
   - `Traldisporta_API_Maintenance_Docs.postman_collection.json`
   - `Traldisporta_API.postman_environment.json`
4. Activa el entorno `TRALDISPORTA API - Public`.
5. En el entorno, define variables:
   - `baseUrl` (por defecto: `https://api-traldisporta.com/api/v1`)
   - `apiKey` (tu API key; no publicarla con valor real)

## Publicar documentacion

1. Abre la coleccion `TRALDISPORTA API - Technical Maintenance`.
2. Revisa que la variable `apiKey` no tenga valor real en la coleccion.
3. En el menu `...` de la coleccion, elige `Publish Docs`.
4. Configura:
   - titulo: `TRALDISPORTA API - Technical Maintenance`
   - visibilidad: privada o publica segun politica interna
   - entorno: `TRALDISPORTA API - Public` si quieres mostrar `baseUrl`
5. Publica.

## Recomendacion de seguridad

Antes de publicar, comprobar que no se exportan:

- API keys reales.
- passwords.
- tokens de servicios externos.
- endpoints internos que no deban ser visibles para terceros.

Referencia: [Postman Documenter](https://documenter.getpostman.com/).

