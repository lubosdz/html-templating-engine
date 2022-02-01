HTML Templating Engine
======================

Simple, fast and flexible HTML templating engine for PHP 7.0 - 8.1 with zero configuration and no dependencies.
Supports basic control structures (IF, FOR, SET), dynamic directives and public object properties.
It is similar to [Twig](https://twig.symfony.com/) or [Blade](https://laravel.com/docs/8.x/blade), though much simpler.

It can be used to render e.g. an invoice or a contract from HTML markup edited in a WYSIWYG editor and turn it optionally into a PDF or MS Word file.
Version with features specific for [Yii2 framework](https://www.yiiframework.com/) can be found at [lubosdz/yii2-template-engine](https://github.com/lubosdz/yii2-template-engine).


Installation
============

```bash
$ composer require "lubosdz/html-templating-engine"
```

or via `composer.json`:

```bash
	"require": {
		...
		"lubosdz/html-templating-engine": "^1.0",
		...
	},
```


Basic usage
===========

Initiate templating engine:

~~~php
use lubosdz\html\TemplatingEngine;
$engine = new TemplatingEngine();

// optionally adjust date or time formatting
$engine->defaultDateFormat = "d/m/Y";
$engine->defaultTimeFormat = "H:i";
~~~

Use method `$engine->render($html [, $values])` to generate HTML output:

* $html = source HTML markup with placeholders like `<h1>{{ processMe }}</h1>`
* $values = array of values like pairs `[processMe => value]` to be injected into or evaluated inside placeholders

Once output generated, it can be e.g. supplied to [PDF](https://github.com/tecnickcom/TCPDF)
or [MS Word](https://github.com/PHPOffice/PHPWord) renderer to produce a PDF or MS Word file respectively.


Simple placeholders
-------------------

Templating engine collects all `placeholders` within supplied HTML markup and attempts to replace them with matching `$values` or evaluate as control structure.
Placeholders that have not been processed are left untouched by default. This behaviour is suitable for development.
In production, setting `$engine->setForceReplace(true)` can be set to replace unprocessed placeholders with empty string.

~~~php
$html = 'Hello <b>{{ who }}</b>!';
$values = ['who' => 'world'];
echo $engine->render($html, $values);
// output: "Hello <b>world</b>!"
~~~


Built-in directives
-------------------

Template engine comes with couple of generally usable methods for basic date,
time and string manipulation - `directives`. These can be referenced directly
inside supplied HTML markup. Directives use the pipe operator `|` to chain
operations within the placeholder.

~~~php
$html = $engine->render('Generated on {{ today }} at {{ time }}.');
// output: "Generated on 31/12/2021 at 23:59."

echo $engine->render('Meet me at {{ now(7200) | time }}.');
// output example, if now is 8:30: "Meet me at 10:30." (shift +2 hours = 7200 secs)

echo $engine->render('My name is {{ user.name | escape }}.', ['user' => [
	'name' => '<John>',
]]);
// filtered output: "My name is &lt;John&gt;."

echo $engine->render('Hello {{ user.name | truncate(5) | e }}.', ['user' => [
	'name' => '<John>',
]]);
// truncated and filtered output: "Hello &lt;John...."
~~~

Engine also supports accessing public properties within objects.
For example we can access public property "name" in object "Customer".

~~~php
$customer = Customer::find(123); // this will find some customer ID. 123
$customer->name = 'John Doe';

$html = $engine->render('Hello {{ customer.name }}.', [
	'customer' => $customer
]);
// output e.g.: "Hello John Doe."
~~~


Dynamic directives
------------------

Dynamic directives allows extending functionality for chaining operations inside
parsed placeholders. They can be added at a runtime as
**callable anonymous functions accepting arguments**.

In the following example we will attach dynamic directive named `coloredText`
and render output with custom inline CSS:


```php
// attach dynamic directive (function) accepting 2 arguments
$engine->setDirective('coloredText', function($text, $color){
	return "<span style='color: {$color}'>{$text}</span>";
});

// process template - we can set different color in each call
echo $engine->render("This is {{ output | coloredText(yellow) }}", [
	'output' => 'colored text',
]);
// output: "This is <span style='color: yellow'>colored text</span>"
```


IF .. ELSEIF .. ELSE .. ENDIF
-----------------------------

Control structure `IF .. ELSEIF .. ELSE .. ENDIF` is supported:

~~~php
$templateHtml = "
{{ if countOrders > 0 }}
	<h3>Thank you!</h3>
{{ else }}
	<h3>Sure you want to leave?</h3>
{{ endif }}
";

$orders = $customer->findOrders(); // will return array of orders
$values = [
	'countOrders' => count($orders);
];

echo $engine->render($templateHtml, $values);
// output e.g.: "<h3>Thank you!</h3>" - if some order found
~~~


FOR ... ELSEFOR .. ENDFOR
-------------------------

Structure `FOR ... ELSEFOR .. ENDFOR` will create loop:

~~~php
$templateHtml = "
<table>
{{ for item in items }}
	{{ SET subtotal = item.qty * item.price * (100 + item.vat) / 100 }}
	<tr>
		<td>#{{ loop.index }}</td>
		<td>{{ item.description }}</td>
		<td>{{ item.qty }}</td>
		<td>{{ item.price | round(2) }}</td>
		<td>{{ item.vat | round(2) }}%</td>
		<td>{{ subtotal | round(2) }} &euro;</td>
	</tr>
	{{ SET total = total + subtotal }}
{{ endfor }}
</table>
<p>Amount due: <b> {{ total | round(2) }} Eur</b></p>
";

$values = [
	'items' => [
		['description' => 'Item one', 'qty' => 1, 'price' => 1, 'vat' => 10],
		['description' => 'Item two', 'qty' => 2, 'price' => 2, 'vat' => 20],
		['description' => 'Item three', 'qty' => 3, 'price' => 3, 'vat' => 30],
		['description' => 'Item four', 'qty' => 4, 'price' => 4, 'vat' => 40],
	]
];

$html = $engine->render($templateHtml, $values);
// outputs valid HTML table with items e.g.: "<table><tr><td>#1</td><td> ..."
~~~

Following auxiliary variables are accessible inside each loop:

* `index` .. (int) 1-based iteration counter
* `index0` .. (int) 0-based iteration counter
* `length` .. (int) total number of items/iterations
* `first` .. (bool) true on first iteration
* `last` .. (bool) true on last iteration



SET command
-----------

Allows manipulating local template variables, such as count totals:

```php
{{ SET subtotal = item.qty * item.price * (100 + item.vat) / 100 }}
{{ SET total = total + subtotal }}
```

See also example under `FOR`.

Note: shorthand syntax `+=` e.g. `SET total += subtotal` is NOT supported.



Rendering PDF or MS Word files
------------------------------

Rendering engine allows user to safely change output without any programming knowledge.
To add an extra value, we can also turn rendered HTML output into professionally
looking [PDF](https://github.com/tecnickcom/TCPDF) or [MS Word](https://github.com/PHPOffice/PHPWord) file:

~~~php

// first process HTML template and input values
$htmlOutput = $engine->render($htmlMarkup, $values);

// then generate PDF file:
$pdf = new \TCPDF();
$pdf->writeHTML($htmlOutput);
$path = $pdf->Output('/save/path/my-invoice.pdf');

// or generate MS Word file:
$word = new \PhpOffice\PhpWord\PhpWord();
$section = $word->addSection();
\PhpOffice\PhpWord\Shared\Html::addHtml($section, $htmlOutput);
$writer = \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007');
$writer->save('/save/path/my-invoice.docx');
~~~


Tips & notes
============

* see `tests/TemplatingEngineTest.php` for more examples
* Running tests via phpunit on [NuSphere's PhpEd](http://www.nusphere.com/) in debug mode:

> use `-d` parameter to inject debugging arguments, e.g. `> phpunit -d DBGSESSID=12655478@127.0.0.1:7869 %*`

* Adding functionality - simply extend `TemplatingEngine` class:

~~~php
class MyRenderer extends \lubosdz\html\TemplatingEngine
{
	// your code, custom directives, override parent methods ..
}
~~~


Changelog
=========

1.0.0, released 2022-02-01
--------------------------

* initial release
