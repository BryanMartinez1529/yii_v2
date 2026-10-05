# API REST con Yii 2, paso a paso

Esta guía explica cómo está construida la API de productos de este proyecto. Cada paso corresponde a un archivo real del repositorio, así que puedes leer la explicación y abrir el código al lado.

**Qué hace la API:** un CRUD de productos en `/v1/products`. Leer es público; crear, modificar y borrar exigen un token.

> **Actualización:** después de escribir esta guía se añadieron los proveedores. Ahora todo producto necesita un `supplier_id`, el modelo `Product` tiene más reglas de las que se muestran aquí y la parte común de los controladores se movió a `ApiController`. Los detalles están en [PROVEEDORES.md](PROVEEDORES.md).

| Método | URL | Acción | ¿Token? |
|---|---|---|---|
| `GET` | `/v1/products` | Lista paginada | No |
| `GET` | `/v1/products/1` | Detalle de un producto | No |
| `POST` | `/v1/products` | Crear | Sí |
| `PUT` / `PATCH` | `/v1/products/1` | Actualizar | Sí |
| `DELETE` | `/v1/products/1` | Borrar | Sí |

---

## Requisitos

- **PHP 8.2 o superior.** El `composer.json` del proyecto lo exige. Compruébalo con `php -v`. Si tienes una versión menor, cualquier comando `php yii ...` falla con *"Your Composer dependencies require a PHP version >= 8.2.0"*; en ServBay se soluciona instalando PHP 8.2+ y poniéndolo como versión por defecto.
- Extensión `pdo_sqlite` (viene activada en casi todas las instalaciones).

---

## Paso 1 — Base de datos

Archivo: [config/db.php](config/db.php)

```php
return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'sqlite:' . dirname(__DIR__) . '/runtime/api.db',
    'charset' => 'utf8',
];
```

Se usa **SQLite** porque la base de datos es un simple archivo (`runtime/api.db`): no hay que levantar un servidor ni configurar usuario y contraseña.

Para usar MySQL, cambia el `dsn` por `mysql:host=localhost;dbname=tu_base` y añade `username` y `password`. El resto del código no cambia: Yii abstrae el motor.

---

## Paso 2 — Migración: crear la tabla

Archivo: [migrations/m261005_000000_create_product_table.php](migrations/m261005_000000_create_product_table.php)

Una migración es una clase que describe un cambio en la base de datos. Así la estructura queda versionada junto al código.

```php
public function safeUp(): void
{
    $this->createTable('{{%product}}', [
        'id' => $this->primaryKey(),
        'name' => $this->string(120)->notNull(),
        'description' => $this->text(),
        'price' => $this->decimal(10, 2)->notNull(),
        'stock' => $this->integer()->notNull()->defaultValue(0),
        'created_at' => $this->integer()->notNull(),
        'updated_at' => $this->integer()->notNull(),
    ]);
    // ...además inserta 3 productos de ejemplo
}
```

- `safeUp()` aplica el cambio y `safeDown()` lo deshace.
- Si creas una migración nueva, genera el archivo con `php yii migrate/create nombre`.

Ejecútala:

```bash
php yii migrate
```

Para deshacerla: `php yii migrate/down`.

---

## Paso 3 — Modelo

Archivo: [models/Product.php](models/Product.php)

El modelo extiende `ActiveRecord`: cada objeto `Product` representa una fila de la tabla. Tiene cuatro partes:

**1. `tableName()`** — a qué tabla apunta.

**2. `behaviors()`** — `TimestampBehavior` rellena `created_at` y `updated_at` automáticamente al guardar.

**3. `rules()`** — las validaciones. Son las que protegen la API: si los datos de un `POST` no las cumplen, Yii responde `422` con la lista de errores, sin escribir nada más.

```php
return [
    [['name', 'price'], 'required'],
    [['name', 'description'], 'trim'],
    ['name', 'string', 'max' => 120],
    ['description', 'string'],
    ['price', 'number', 'min' => 0],
    ['stock', 'integer', 'min' => 0],
    ['stock', 'default', 'value' => 0],
];
```

Solo los atributos que aparecen en `rules()` se pueden asignar desde la petición. Por eso un cliente no puede enviar `id` o `created_at` y sobrescribirlos.

**4. `fields()`** — qué campos salen en el JSON y con qué formato. Aquí el precio se convierte a número y las fechas a formato ISO 8601:

```php
'price' => fn (self $model): float => (float) $model->price,
'created_at' => fn (self $model): string => date(DATE_ATOM, $model->created_at),
```

Si un día añades una columna sensible a la tabla, no aparecerá en la API a menos que la pongas aquí.

---

## Paso 4 — Módulo `v1`

Archivo: [modules/v1/Module.php](modules/v1/Module.php)

Un módulo es una mini aplicación dentro de la aplicación. Se usa por dos razones:

- **Versionado:** las URL empiezan por `/v1/`. Si mañana rompes compatibilidad, creas `modules/v2` y los clientes antiguos siguen funcionando.
- **Aislamiento:** el proyecto también tiene una web HTML (`SiteController`). El módulo permite configurar la API de forma distinta sin tocar la web.

Esa configuración está en `init()`:

```php
Yii::$app->user->enableSession = false;   // API sin estado: no usa sesión ni cookies
Yii::$app->user->loginUrl = null;         // sin token → 401, no redirección al login

Yii::$app->response->format = Response::FORMAT_JSON;  // los errores salen en JSON...
Yii::$app->errorHandler->errorAction = null;          // ...y no con la página HTML de site/error
```

---

## Paso 5 — Controlador

Archivo: [modules/v1/controllers/ProductController.php](modules/v1/controllers/ProductController.php)

```php
class ProductController extends ActiveController
{
    public $modelClass = Product::class;
    // ...
}
```

Con solo indicar `$modelClass`, `yii\rest\ActiveController` ya trae implementadas las acciones `index`, `view`, `create`, `update`, `delete` y `options`. No hay que escribir ninguna.

Se añaden dos ajustes:

**Serializador con sobre (`collectionEnvelope`)** — hace que la lista devuelva los datos de paginación dentro del cuerpo:

```json
{ "items": [ ... ], "_links": { ... }, "_meta": { "totalCount": 3, "pageCount": 1, "currentPage": 1, "perPage": 20 } }
```

**Autenticación** — se añade el filtro `HttpBearerAuth` a todas las acciones menos las de lectura:

```php
$behaviors['authenticator'] = [
    'class' => HttpBearerAuth::class,
    'except' => ['index', 'view', 'options'],
];
```

El filtro lee la cabecera `Authorization: Bearer <token>` y llama a `User::findIdentityByAccessToken()` en [models/User.php](models/User.php). Ese modelo viene con dos usuarios de prueba escritos en el código:

| Usuario | Token |
|---|---|
| admin | `100-token` |
| demo | `101-token` |

> Estos tokens fijos sirven para aprender. En un proyecto real, los usuarios se guardan en base de datos y cada uno tiene un token aleatorio generado con `Yii::$app->security->generateRandomString()`.

---

## Paso 6 — Configuración de la aplicación

Archivo: [config/web.php](config/web.php)

Tres cambios conectan todo lo anterior.

**1. Registrar el módulo:**

```php
'modules' => [
    'v1' => ['class' => \app\modules\v1\Module::class],
],
```

**2. Aceptar JSON en el cuerpo de las peticiones.** Por defecto Yii solo entiende formularios; sin esto, un `POST` con JSON llegaría vacío:

```php
'request' => [
    'parsers' => [
        'application/json' => \yii\web\JsonParser::class,
    ],
],
```

**3. URL limpias y regla REST:**

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        ['class' => \yii\rest\UrlRule::class, 'controller' => 'v1/product'],
    ],
],
```

`yii\rest\UrlRule` genera todas las rutas de la tabla del principio a partir de una sola línea, y pluraliza el nombre (`product` → `/v1/products`). También traduce el verbo HTTP a la acción: `GET` → `index`/`view`, `POST` → `create`, `PUT`/`PATCH` → `update`, `DELETE` → `delete`.

---

## Paso 7 — Arrancar y probar

```bash
php yii migrate      # solo la primera vez
php yii serve        # servidor en http://localhost:8080
```

**Listar**

```bash
curl http://localhost:8080/v1/products
```

**Ver uno**

```bash
curl http://localhost:8080/v1/products/1
```

**Crear** (necesita token) → `201 Created`

```bash
curl -X POST http://localhost:8080/v1/products \
  -H "Authorization: Bearer 100-token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Webcam HD", "price": 35.5, "stock": 7, "supplier_id": 1}'
```

**Actualizar** → `200 OK`. Basta con enviar los campos que cambian.

```bash
curl -X PUT http://localhost:8080/v1/products/4 \
  -H "Authorization: Bearer 100-token" \
  -H "Content-Type: application/json" \
  -d '{"stock": 99}'
```

**Borrar** → `204 No Content`

```bash
curl -X DELETE http://localhost:8080/v1/products/4 \
  -H "Authorization: Bearer 100-token"
```

### Respuestas de error

| Caso | Código | Cuerpo |
|---|---|---|
| Sin token o token incorrecto | `401` | `{"name":"Unauthorized","message":"Your request was made with invalid credentials.", ...}` |
| El producto no existe | `404` | `{"name":"Not Found","message":"Object not found: 999", ...}` |
| Datos inválidos | `422` | `[{"field":"name","message":"Name cannot be blank."}, ...]` |

### Extras que vienen gratis

`ActiveController` soporta estos parámetros en la lista sin escribir código:

| Parámetro | Ejemplo | Efecto |
|---|---|---|
| `page`, `per-page` | `?page=2&per-page=10` | Paginación |
| `sort` | `?sort=-price` | Ordenar (el `-` es descendente) |
| `fields` | `?fields=id,name,price` | Devolver solo esos campos |

Además, si el cliente envía `Accept: application/xml`, la respuesta sale en XML.

---

## Resumen: recorrido de una petición

`POST /v1/products` pasa por estas piezas, en orden:

1. **`urlManager`** ([config/web.php](config/web.php)) — convierte la URL y el verbo en la ruta `v1/product/create`.
2. **`Module`** ([modules/v1/Module.php](modules/v1/Module.php)) — desactiva la sesión y fija JSON como formato.
3. **`ProductController`** — el filtro `HttpBearerAuth` comprueba el token; si falla, `401`.
4. **`JsonParser`** — convierte el cuerpo JSON en un array.
5. **`Product`** ([models/Product.php](models/Product.php)) — valida con `rules()`; si falla, `422`. Si pasa, guarda la fila.
6. **`Serializer`** — convierte el modelo a JSON usando `fields()` y responde `201`.

## Para añadir otro recurso

Por ejemplo, categorías:

1. Migración que cree la tabla `category`.
2. Modelo `app\models\Category` con sus `rules()`.
3. Controlador `modules/v1/controllers/CategoryController.php` con `$modelClass = Category::class`.
4. Añadir `'v1/category'` a la regla de URL: `'controller' => ['v1/product', 'v1/category']`.
