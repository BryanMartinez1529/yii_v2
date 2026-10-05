# Formulario con el patrón MVC en Yii 2, paso a paso

Esta guía explica cómo están construidas las dos páginas web de productos de este proyecto:

| URL | Qué muestra | ¿Requiere login? |
|---|---|---|
| `/product` | Tabla con todos los productos | No |
| `/product/create` | Formulario para crear un producto | Sí (`admin` / `admin`) |

Usan la misma tabla y el mismo modelo que la API (ver [API_REST.md](API_REST.md)), así que un producto creado en el formulario aparece también en `GET /v1/products`.

> **Actualización:** después de escribir esta guía se añadieron los proveedores. El formulario de producto tiene ahora un desplegable de proveedor obligatorio y la lista una columna más. Los detalles están en [PROVEEDORES.md](PROVEEDORES.md).

---

## Qué es MVC

MVC separa el código en tres piezas, cada una con una sola responsabilidad:

| Pieza | Responsabilidad | En este proyecto |
|---|---|---|
| **Modelo** | Los datos y sus reglas: qué es válido y cómo se guarda | [models/Product.php](models/Product.php) |
| **Vista** | El HTML que ve el usuario | [views/product/create.php](views/product/create.php), [views/product/index.php](views/product/index.php) |
| **Controlador** | Recibe la petición, usa el modelo y elige qué vista mostrar | [controllers/ProductController.php](controllers/ProductController.php) |

La regla práctica: la vista no toca la base de datos, el modelo no genera HTML, y el controlador no valida ni dibuja; solo coordina.

Yii conecta las piezas por **convención de nombres**, sin configuración:

```
URL /product/create
     │        └── acción  → método actionCreate()
     └── controlador      → clase app\controllers\ProductController

$this->render('create')   → archivo views/product/create.php
```

---

## Paso 1 — El modelo (M)

Archivo: [models/Product.php](models/Product.php)

Ya existía desde la API y **no hubo que cambiarle nada**. Esa es la ventaja de MVC: las validaciones viven en un solo sitio y sirven para todo lo que use el modelo.

```php
public function rules(): array
{
    return [
        [['name', 'price'], 'required'],
        [['name', 'description'], 'trim'],
        ['name', 'string', 'max' => 120],
        ['description', 'string'],
        ['price', 'number', 'min' => 0],
        ['stock', 'integer', 'min' => 0],
        ['stock', 'default', 'value' => 0],
    ];
}
```

Para el formulario, `rules()` hace tres trabajos:

1. **Valida en el servidor** al guardar.
2. **Valida en el navegador:** el formulario genera JavaScript a partir de estas mismas reglas y muestra el error al salir del campo, sin recargar.
3. **Decide qué campos se aceptan:** solo los atributos que aparecen en alguna regla se cargan desde el formulario. Nadie puede colar un `id` o un `created_at` manipulando el HTML.

---

## Paso 2 — El controlador (C)

Archivo: [controllers/ProductController.php](controllers/ProductController.php)

Tiene dos acciones. Cada método público `actionAlgo()` es una página.

### `actionIndex()` — la lista

```php
public function actionIndex(): string
{
    $dataProvider = new ActiveDataProvider([
        'query' => Product::find(),
        'pagination' => ['pageSize' => 10],
        'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
    ]);

    return $this->render('index', ['dataProvider' => $dataProvider]);
}
```

`ActiveDataProvider` envuelve la consulta y se encarga de paginar y ordenar. El controlador se lo pasa a la vista; el segundo argumento de `render()` son las variables que la vista recibe.

### `actionCreate()` — el formulario

```php
public function actionCreate(): Response|string
{
    $model = new Product();

    if ($model->load($this->request->post()) && $model->save()) {
        Yii::$app->session->setFlash('success', "Product \"{$model->name}\" was created.");

        return $this->redirect(['index']);
    }

    return $this->render('create', ['model' => $model]);
}
```

Esta única acción atiende **dos peticiones distintas**:

| Petición | `load()` | Qué pasa |
|---|---|---|
| `GET` (abrir la página) | No hay datos → `false` | Muestra el formulario vacío |
| `POST` con datos inválidos | `true`, pero `save()` → `false` | Vuelve a mostrar el formulario, con lo que el usuario escribió y los errores |
| `POST` con datos válidos | `true` y `save()` → `true` | Guarda, deja un mensaje y redirige a la lista |

- `load()` copia los datos del formulario al modelo.
- `save()` ejecuta `rules()` y, si todo es válido, hace el `INSERT`.
- `setFlash()` guarda un mensaje que se muestra **una sola vez** en la siguiente página.
- El `redirect()` tras guardar evita que el producto se cree dos veces si el usuario recarga la página.

### `behaviors()` — quién puede entrar

```php
'access' => [
    'class' => AccessControl::class,
    'only' => ['create'],
    'rules' => [
        ['actions' => ['create'], 'allow' => true, 'roles' => ['@']],
    ],
],
```

`'@'` significa "usuario con sesión iniciada". Un visitante que abre `/product/create` es enviado a `/site/login` y, tras entrar, vuelve al formulario. Se hizo así para que la web tenga la misma política que la API: leer es público, escribir no.

---

## Paso 3 — La vista del formulario (V)

Archivo: [views/product/create.php](views/product/create.php)

```php
<?php $form = ActiveForm::begin(['id' => 'product-form']); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => true, 'autofocus' => true]) ?>

<?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

<?= $form->field($model, 'price')->input('number', ['min' => 0, 'step' => '0.01']) ?>

<?= $form->field($model, 'stock')->input('number', ['min' => 0, 'step' => 1]) ?>

<?= Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'save-button']) ?>

<?php ActiveForm::end(); ?>
```

Cada `$form->field($model, 'name')` genera cuatro cosas a partir del modelo: la etiqueta, el `<input>`, el hueco para el mensaje de error y la validación en JavaScript. El HTML resultante es:

```html
<input type="text" id="product-name" class="form-control" name="Product[name]" maxlength="120" aria-required="true">
```

Fíjate en `name="Product[name]"`: todos los campos se envían agrupados bajo `Product`, y eso es justo lo que `load()` busca en el controlador.

`ActiveForm::begin()` añade además un campo oculto `_csrf`. Es un token que demuestra que el envío sale de tu propia página; un `POST` sin él recibe `400 Bad Request`.

Al principio del archivo se definen el título y las migas de pan:

```php
$this->title = 'New product';
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
```

La vista solo produce su trozo de contenido. La cabecera, el menú y el pie los pone el *layout* [views/layouts/main.php](views/layouts/main.php), que envuelve a todas las páginas.

---

## Paso 4 — La vista de la lista (V)

Archivo: [views/product/index.php](views/product/index.php)

```php
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'name',
        'description:ntext',
        'price:decimal',
        'stock:integer',
        'created_at:datetime',
    ],
]) ?>
```

`GridView` dibuja la tabla completa, con cabeceras clicables para ordenar y la paginación, a partir del `$dataProvider` que mandó el controlador. Lo que va tras los dos puntos es el formato: `created_at:datetime` convierte el número guardado en base de datos en una fecha legible.

**Seguridad:** `GridView` y `Html::encode()` escapan el texto antes de mostrarlo. Si alguien guarda `<b>hola</b>` como nombre, se ve como texto, no se ejecuta como HTML. Cuando imprimas un dato del usuario a mano, usa siempre `Html::encode()`.

---

## Paso 5 — El enlace en el menú

Archivo: [views/layouts/_header.php](views/layouts/_header.php)

```php
[
    'label' => 'Products',
    'url' => ['/product/index'],
],
```

La URL se escribe como ruta (`controlador/acción`) y Yii la convierte en `/product/index`. No hizo falta tocar [config/web.php](config/web.php): las rutas `controlador/acción` funcionan solas.

---

## Paso 6 — Probarlo

```bash
php yii serve
```

1. Abre `http://localhost:8080/product`: verás la tabla con los productos de ejemplo.
2. Pulsa **New product**: como no has iniciado sesión, te lleva al login.
3. Entra con `admin` / `admin`: vuelves al formulario.
4. Pulsa **Save** con el nombre vacío o un precio negativo: aparecen los errores bajo cada campo.
5. Rellénalo bien y guarda: vuelves a la lista con el mensaje verde y el producto nuevo arriba.
6. Abre `http://localhost:8080/v1/products`: el mismo producto está en la API.

---

## Resumen: recorrido de un envío del formulario

1. El navegador envía `POST /product/create` con `Product[name]`, `Product[price]`… y `_csrf`.
2. Yii comprueba el token `_csrf`; si falta, responde `400`.
3. **Controlador** — `AccessControl` comprueba que hay sesión; si no, redirige al login.
4. **Controlador** — `actionCreate()` crea un `Product` y llama a `load()` y `save()`.
5. **Modelo** — `rules()` valida. Si falla, `save()` devuelve `false` y el modelo se queda con los errores.
6. **Vista** — con errores, el controlador renderiza `create` otra vez y `ActiveForm` pinta cada error bajo su campo. Sin errores, el controlador redirige a `index`, donde `GridView` muestra la tabla actualizada.

## Para añadir editar y borrar

Siguen el mismo patrón:

1. **Editar:** una acción `actionUpdate(int $id)` idéntica a `actionCreate()`, pero que empieza con `Product::findOne($id)` en vez de `new Product()`. La vista del formulario se puede reutilizar tal cual.
2. **Borrar:** una acción `actionDelete(int $id)` que llama a `$model->delete()` y redirige. Debe aceptar solo `POST` (con `VerbFilter`, como hace `logout` en [controllers/SiteController.php](controllers/SiteController.php)).
3. Añade ambas acciones a `only` y `actions` en `behaviors()` para que exijan login.
