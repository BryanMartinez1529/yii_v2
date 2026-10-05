# Proveedores y su relación con los productos, paso a paso

Esta guía explica cómo se añadió el modelo **Proveedor** (`Supplier`) y cómo quedó asociado a los productos.

**La regla de negocio:** todo producto pertenece a un proveedor, y un proveedor puede tener muchos productos. Es una relación *uno a muchos*:

```
supplier (1) ──────< (muchos) product
   id  ◄──────────────  supplier_id
```

La asociación se guarda en una sola columna: `product.supplier_id` contiene el `id` del proveedor. A eso se le llama *clave foránea*.

---

## El flujo para crear

Como el producto necesita un proveedor, **el proveedor se crea primero**.

### Desde la web

1. Inicia sesión (`admin` / `admin`).
2. Menú **Suppliers** → **New supplier** → rellena el nombre → **Save**.
3. Menú **Products** → **New product**.
4. Elige el proveedor en el desplegable **Supplier**, rellena el resto → **Save**.
5. La lista de productos muestra el nombre del proveedor en su columna.

Si el proveedor que necesitas no está en el desplegable, el enlace *Create a new supplier* bajo el campo te lleva a crearlo.

### Desde la API

```bash
# 1. Crear el proveedor → responde 201 con su "id"
curl -X POST http://localhost:8080/v1/suppliers \
  -H "Authorization: Bearer 100-token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Cables Quito", "email": "ventas@cablesquito.example"}'

# 2. Crear el producto usando ese id en "supplier_id"
curl -X POST http://localhost:8080/v1/products \
  -H "Authorization: Bearer 100-token" \
  -H "Content-Type: application/json" \
  -d '{"name": "Cable HDMI 2 m", "price": 6.5, "stock": 40, "supplier_id": 3}'
```

### Qué pasa si te saltas el proveedor

| Envío | Respuesta |
|---|---|
| Sin `supplier_id` | `422` — `Supplier cannot be blank.` |
| `supplier_id` de un proveedor que no existe | `422` — `Supplier is invalid.` |

En la web aparecen los mismos mensajes bajo el desplegable.

---

## Paso 1 — Migración

Archivo: [migrations/m261005_010000_create_supplier_table.php](migrations/m261005_010000_create_supplier_table.php)

Hace cuatro cosas, en este orden:

**1. Crea la tabla `supplier`** con `name` (obligatorio y único), `email` y `phone`, e inserta dos proveedores de ejemplo.

**2. Añade la columna que une las tablas:**

```php
$this->addColumn('{{%product}}', 'supplier_id', $this->integer());
$this->createIndex('idx-product-supplier_id', '{{%product}}', 'supplier_id');
```

El índice hace rápida la consulta "productos de este proveedor".

**3. Asigna un proveedor a los productos que ya existían,** para que ninguno quede huérfano:

```php
$this->update('{{%product}}', ['supplier_id' => 1]);
```

Por eso los tres productos de ejemplo aparecen ahora con el proveedor "Periféricos del Sur".

**4. Crea la clave foránea, salvo en SQLite:**

```php
if ($this->db->driverName !== 'sqlite') {
    $this->addForeignKey('fk-product-supplier_id', '{{%product}}', 'supplier_id', '{{%supplier}}', 'id', 'RESTRICT', 'CASCADE');
}
```

Una clave foránea hace que la propia base de datos rechace un `supplier_id` inexistente. SQLite no permite añadirla a una tabla que ya existe, así que con la configuración actual **la integridad la garantiza solo el modelo** (paso 3). Si cambias a MySQL, la migración la crea, y `RESTRICT` impide además borrar un proveedor que tenga productos.

Aplícala con:

```bash
php yii migrate
```

---

## Paso 2 — Modelo `Supplier`

Archivo: [models/Supplier.php](models/Supplier.php)

Tiene la misma estructura que `Product`. Lo nuevo son dos cosas.

**La regla `unique`,** que impide dos proveedores con el mismo nombre:

```php
['name', 'required'],
['name', 'unique'],
['email', 'email'],
```

**La relación hacia los productos:**

```php
public function getProducts(): ActiveQuery
{
    return $this->hasMany(Product::class, ['supplier_id' => 'id']);
}
```

Se lee así: *un proveedor tiene muchos `Product`, aquellos cuyo `supplier_id` es igual a mi `id`.*

---

## Paso 3 — Modelo `Product`: la asociación

Archivo: [models/Product.php](models/Product.php)

Aquí es donde el producto queda atado a un proveedor. Son tres cambios.

**1. Reglas: el proveedor es obligatorio y tiene que existir.**

```php
[['name', 'price', 'supplier_id'], 'required'],
['supplier_id', 'integer'],
['supplier_id', 'exist', 'targetClass' => Supplier::class, 'targetAttribute' => 'id'],
```

- `required` es lo que cumple el "debe estar asociado".
- `exist` consulta la tabla `supplier` y rechaza un id inventado.

Como las reglas viven en el modelo, se aplican tanto al formulario web como a la API sin escribir nada más en ninguno de los dos.

**2. La relación inversa:**

```php
public function getSupplier(): ActiveQuery
{
    return $this->hasOne(Supplier::class, ['id' => 'supplier_id']);
}
```

*Un producto tiene un `Supplier`, aquel cuyo `id` es igual a mi `supplier_id`.*

Un método `getSupplier()` se usa como si fuera una propiedad. Yii hace la consulta la primera vez que la lees:

```php
$product = Product::findOne(1);
echo $product->supplier->name;      // "Periféricos del Sur"

$supplier = Supplier::findOne(1);
foreach ($supplier->products as $product) { ... }
```

**3. `attributeLabels()`** cambia la etiqueta del campo de "Supplier ID" a "Supplier", en el formulario y en los mensajes de error.

---

## Paso 4 — API

**Nuevo recurso `/v1/suppliers`.** Archivo: [modules/v1/controllers/SupplierController.php](modules/v1/controllers/SupplierController.php)

| Método | URL | Acción | ¿Token? |
|---|---|---|---|
| `GET` | `/v1/suppliers` | Lista | No |
| `GET` | `/v1/suppliers/1` | Detalle | No |
| `POST` | `/v1/suppliers` | Crear | Sí |
| `PUT` / `PATCH` | `/v1/suppliers/1` | Actualizar | Sí |

No hay `DELETE`: el controlador quita esa acción (responde `404`), porque borrar un proveedor dejaría sus productos sin proveedor.

**Clase base común.** La autenticación y el formato de las listas eran idénticos para productos y proveedores, así que se movieron a [modules/v1/controllers/ApiController.php](modules/v1/controllers/ApiController.php). Los dos controladores heredan de ella y solo indican su modelo:

```php
class ProductController extends ApiController
{
    public $modelClass = Product::class;
}
```

**Ruta.** En [config/web.php](config/web.php) la regla ahora cubre los dos controladores:

```php
['class' => \yii\rest\UrlRule::class, 'controller' => ['v1/product', 'v1/supplier']],
```

**Datos relacionados con `expand`.** El producto devuelve siempre `supplier_id`. Para recibir además los datos del proveedor en la misma respuesta, pídelo con `?expand=`:

```bash
curl "http://localhost:8080/v1/products/1?expand=supplier"
```

```json
{
  "id": 1,
  "supplier_id": 1,
  "name": "Teclado mecánico",
  "supplier": { "id": 1, "name": "Periféricos del Sur", "email": "..." }
}
```

Y al revés, un proveedor con sus productos:

```bash
curl "http://localhost:8080/v1/suppliers/1?expand=products"
```

Esto funciona porque cada modelo declara la relación en `extraFields()`. Lo que está en `fields()` sale siempre; lo que está en `extraFields()` solo cuando se pide.

---

## Paso 5 — Páginas web

**Proveedores.** Lista y formulario, con el mismo patrón MVC de [FORMULARIO_MVC.md](FORMULARIO_MVC.md):

- Controlador: [controllers/SupplierController.php](controllers/SupplierController.php)
- Vistas: [views/supplier/index.php](views/supplier/index.php) y [views/supplier/create.php](views/supplier/create.php)
- Enlace **Suppliers** en el menú: [views/layouts/_header.php](views/layouts/_header.php)

**Desplegable en el formulario de producto.** El controlador [controllers/ProductController.php](controllers/ProductController.php) prepara la lista de proveedores y se la pasa a la vista:

```php
// [id => name] pairs for the supplier dropdown
$suppliers = Supplier::find()->select(['name', 'id'])->orderBy('name')->indexBy('id')->column();

return $this->render('create', ['model' => $model, 'suppliers' => $suppliers]);
```

El resultado es un array `[1 => 'Periféricos del Sur', 2 => 'Pantallas Andinas']`. La vista [views/product/create.php](views/product/create.php) lo convierte en un `<select>`:

```php
<?= $form->field($model, 'supplier_id')
    ->dropDownList($suppliers, ['prompt' => 'Select a supplier...']) ?>
```

La consulta la hace el controlador y no la vista: es la separación de MVC.

**Columna en la lista de productos.** En [views/product/index.php](views/product/index.php):

```php
[
    'attribute' => 'supplier_id',
    'value' => 'supplier.name',
],
```

`supplier.name` recorre la relación: de cada producto toma su proveedor y de este el nombre.

En el controlador, la consulta lleva `->with('supplier')`:

```php
'query' => Product::find()->with('supplier'),
```

Sin eso, Yii haría una consulta por cada fila para buscar su proveedor (10 productos = 11 consultas). Con `with()` trae todos los proveedores necesarios en una sola consulta extra.

---

## Resumen: qué garantiza la asociación

| Garantía | Dónde está |
|---|---|
| Un producto no se guarda sin proveedor | Regla `required` en `Product` |
| El proveedor tiene que existir | Regla `exist` en `Product` |
| Los productos antiguos tienen proveedor | `update()` en la migración |
| No se borra un proveedor desde la API | `unset($actions['delete'])` en `SupplierController` |
| La base de datos lo impone también | Clave foránea — **solo en MySQL**, no en SQLite |
