Coercive Utility Render
=======================

A simple template rendering system. [beta]

Get
---
```
composer require coercive/render
```

Load
----
```php
use Coercive\Utility\Render\Render;

# Load
$render = new Render( 'TEMPLATES_ROOT_PATH' );

# Set globals vars (global for all views and sub injected views)
# use $render->setGlobalDatas for override or add for conserve previous added
$render->addGlobalDatas([
	'app' => new App(),
	'header' => new Header(),
	'handler' => new Handler()
	...
]);

# Set Views Datas
# use $render->setDatas for override or add for conserve previous added
$render->addDatas([
	'title' => 'My Custom Title',
	'content' => 'Lorem Ipsum Text',
	'link' => 'Visit my website : <a href="#">my-web-site.com</a>'
	...
]);

# Set View path
# use $render->setPath for override or add for conserve previous added
$render->setPath('/TemplateName/ViewDir/Viewname');

# Multi views
foreach([...] as $path) {
    $render->addPath($path);
}

# If multiple template, you need to set where load a layout
# Or if you need to load in specific other template for a/b testing or events ...
$render->setTemplate('MyEventTemplate2017');

# Now, Render !
echo $render->render();
```

## Errors
```php
use Coercive\Utility\Render\Render;

# Load
$render = new Render( 'TEMPLATES_ROOT_PATH' );

# You can see all errors like this
foreach($render->getExceptions() as $exception) {
    echo $exception->getMessage();
}

# Or you can log error when it's added with handler
$render->debug(function ($exception) {
    error_log(print_r($exception->getMessage(), true));
});
```

Tree
----
In a template dir, Layout dir/file is required.
```php
Dir: Website
- Dir: Templates
-- Dir: Template
-- Dir: view
--- File: view-1.php
--- File: view-2.php
--- File: view-3.php
-- Dir: layout
--- File: layout.php
--- File: head.php
--- File: header.php
--- File: footer.php
```
The Layout is return by the render method.
So you will load & echo you view inside the layout file.
Even if you don't realy have a template. (empty layout)

Layout
------
```php
<html>
	<head>
		<?php require_once(__DIR__ . DIRECTORY_SEPARATOR . 'head.php') ?>
	</head>
	<body>

		<header>
			<?php require_once(__DIR__ . DIRECTORY_SEPARATOR . 'header.php') ?>
		</header>

		<!-- VIEW CONTENT -->
		<div id="view"><?php
			//------------------------
			// RENDER VIEW IN TEMPLATE
			echo $this->getViews();
			//------------------------?>
		</div>

		<footer>
			<?php require_once(__DIR__ . DIRECTORY_SEPARATOR . 'footer.php') ?>
		</footer>
	</body>
</html>
```

No Template ? Layout is Required
------
Just print your views in an empty layout
```php
echo $this->getViews();
```

Example basic tree
------------------
```php
Dir: Website
	Dir: views
		Dir: Default
			Dir: layout
				File: layout.php
				File: head.php
				File: header.php
				File: footer.php
		Dir: Email
			Dir: layout
				File: layout.php
		Dir: None
			Dir: layout
				File: layout.php
```