#Tagent


####Tag

```text
<ag ATTRIBUTE=VALUE></ag>
```

#####Reserved attributes
`Module`,`Pull`,`Loop`,`Parse`,`Close`,`Refresh`,`Reopen`,`Template`,`Check`,`Debug`,`Header`,`Read`,`Trim`,`Store`,`Restore`,`Help`  

Attributes name are case-sensitive.
The first letter of reserved attribute name is upper-case.

(Recommend) The first letter of the user attributes name are lower-case so as not to name collision.  
The user attributes are used as a properties array $params  (see `Module`, `Pull`, `Loop` and `Refresh`.)  

```text
<ag Module='Foo'></ag>    // single-quotation attribute value
<ag Module="Foo"></ag>    // double-quotation attribute value
<ag Module=Foo></ag>      // non-quoatation attribute value

<!-- variable value  -->
<ag Module='Foo' bar=@var></ag>  // variable attribute value

<!-- escape quotation mark -->
<ag Module='Foo' bar='It\'s cool'></ag>  // escaped single quotation inside single-quotation
<ag Module='Foo' bar="It's cool"></ag>   // non-escape single quotation inside double-quotation

<!-- assign value to variable 'foo' -->
<ag [foo]='bar'>{@foo}</ag>   // literal   temporary use.  
<ag [foo]={@bar}>{@foo}</ag>  // variable  Typical use is bridge to inside tag from outside tag scope.  
```

####Variable

```text
{@scope:name[index][...]|filter|...}
```

option scope, index, filter

```
{@foo}           Scope full(search order Pull,Loop,Currnet module,Global module)
{@m:foo}         Scope module
{foo|raw}        Filter raw
{@foo['bar']}    Literal index 'bar'  with quotation. both valid '',""  
{@foo[bar]}      Not recommend. Literal index 'bar'  without quotation. index [\w]+ only.  
```

Un-quoated literal index is not recomend. because little slow for parsing.

1. In the content. with bracket{}  

```html
  <ag>
    {@foo}, {@m:foo}, {@foo|r},  {@g:foo|json}
    {@foo|url|html} Multiple filters. Apply from left to right.
  </ag>
```

2. In the attribute value or variable index.  without bracket{}  

```html
    <ag foo=@bar ></ag>    Attribute value @bar of `ag tag`.
    {@foo[@bar]}          Index variable @bar.
```

#####Scope(option)  

Variable scope , `Pull`, `Loop`, `Current module`, `Global module`.  

```
(-- nothing --)   Scanning order  Pull, Loop, Current module, Global module  
'p'               Pull scope  
`l`               Loop scope  
`m`               Current module scope  
`g`               Global module scope  
```

case-sensitive  

#####Variable Name  

```text
/\w+/...  /[a-zA-Z0-9_]+/  
```
case-sensitive


#####Filter(option)  

```text
html   or h      htmlspecialchars( ENT_QUOTE, $charset)  config "charset" => 'utf-8'
raw    or r      no filter  
url    or u      urlencode()  
json   or j      json_encode()  
base64 or b      base64_encode()
f'format'        printf formatting  ex |f'%05d'  
+,-d,d,/,%,^,**  numeric operation  ex |+1|-2|*3|/4|%5|^6|**6   ^ or ** is  Exponentiation
d'value'         Default value .  if undifined variable,  output default value.
```
case-sensitive

Default filter

```html
    <ag foo=@bar>    <!-- default non filter  -->
      {@bar}         <!-- default html filter -->
    </ag>
```

it's posible to add user defined filters.  

###Other exmaples

> javascript html script tag   use jQuery parseJSON.  

```html
<ag Module='Foo'>
<script>
$( document ).ready(function() {
    var obj = $.parseJSON('{@bar|json}');
});
</script>
</ag>
```

> javascript script file  use jQuery parseJSON    

```js
/* <ag Header ='javascript' Module='Foo'></ag> */
$( document ).ready(function() {
  var obj = $.parseJSON('{@bar|json}');
});
/* </ag> */
```

> json.php  for ajax.  

```html
<ag Header ='json' Module='Foo' Trim='on'>
    {@bar|json}
</ag>
```

> css.php

```css
/* <ag Header='css'></ag> 
   <ag [border]='1px solid #aaa'> */
div.bar {
  border : {@border};
}
div.baz {
  border : {@border};
}
/* </ag> */
```

> jpeg.php

```html
<ag Header='jpeg' Module='Foo' Trim='on'>
<ag Read={@bar}></ag>
</ag>
```

##Basic Directory Structure 

use composer 

```
[project] - [public] - WEB DOCUMENT ROOT
          |            index.php
          |            (.......)
          |- [ag]-[Module_GLOBAL] -[Pulls]    - *.php 
          |      |                 [Loops]    - *.php
          |      |                 [Templates]- *.tpl
          |      |                 [......]   - *.php
          |      |                 Module.php
          |      |
          |      |-[Module_Foo]----[Pulls]    - *.php
          |                        [Loops]    - *.php
          |                        [Templates]- *.tpl
          |                        [......]   - *.php
          |                        Module.php
          |- bootstrap.php
          |- config.php
          |- [vendor]-[tagent]-[tagent]-[lib]-Agent.php
                                              (......)
```

[ag] directory name is default setting in config.php `'ag_directories'=>array('ag/')` multiple OK  

###Fixed dir/file name, class 

| Directory      | Filename   |Class                   | TAG attribute                           |
|----------------|------------|------------------------|-----------------------------------------|
|[Module\_Foo]   |Module.php  |\Module\_Foo\Module     |`<ag Module='Foo'></ag>`                 |
|[Pulls]         |Bar.php     |\Module\_Foo\Pulls\Bar  |`<ag Module='Foo' Pull='Bar'></ag>`      |
|[Loops]         |Baz.php     |\Module\_Foo\Loops\Baz  |`<ag Module='Foo' Loop='Baz'></ag>`      |
|[Templates]     |Qux.tpl     |                        |`<ag Module='Foo' Template='Qux'></ag>`  |


###Fixed method name  

| Method                | Class                    |TAG attribute                         |
|-----------------------|--------------------------|--------------------------------------|
|onRefresh()            |\Module\_Foo\Module       |`<ag Module='Foo' Refresh='on'></ag>` |
|onClose()              |\Module\_Foo\Module       |`<ag Module='Foo' Close='on'></ag>`   |
|pullBar or pull\_bar() |\Module\_Foo\Module       |`<ag Module='Foo' Pull='Bar'></ag>`   |
|pullBar or pull\_bar() |\Module\_Foo\Pulls\Bar    |`<ag Module='Foo' Pull='Bar'></ag>`   |
|loopBaz or loop\_baz() |\Module\_Foo\Module       |`<ag Module='Foo' Loop='Baz'></ag>`   |
|loopBaz or loop\_baz() |\Module\_Foo\Loops\Baz    |`<ag Module='Foo' Loop='Baz'></ag>`   |

* onRefresh() ... RefreshModuleInterface
* onClose()   ... CloseModuleInterface


>bootstrap.php  example 

```php 
chdir (__DIR__);
require 'vendor/autoload.php';
$agent = \Tagent\Agent::init( require 'config.php' );
```

>config.php example 

```php
return array(
    "debug"              => true,
    "log_reporting"      => E_ALL,
    "shutdown_display"   => true,
    "agent_tag"          => "ag",
    "agent_directories"  => array( "ag/" ),
    "line_offset"        => 0,
    "template_ext"       => ".tpl",
    "db" => array(
        'default'=> array (
            "dsn"      => "mysql:host=localhost;dbname=test",
            "user"     => "username",
            "password" => "passwd",
            "options"  => array(\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC),
        ),
        'foobar'=> array (
            "dsn"   => "sqlite:db/testdb.sqlite3",
        )
    )
);
```


##How to boot

several ways   

1. Preload bootstrap.php in .htaccess.    (Recommended)  
1. Require in the first line of each file (Easy quick start)  
3. Use front-controller                   (Controller is not included)  

###1.Preload bootstrap.php (Recommended)

by .htaccess  

automatically preload bootstrap.php for file extension .tpl and .php(default)  

>.htaccess  

    php_value auto_prepend_file "/path/to/bootstrap.php"

*I's easy, add type. but carefully. There are some risks*  
.tpl .html .js .css and .php(default)  

>.htaccess  

    AddType application/x-httpd-php .tpl .html .js .css
    php_value auto_prepend_file "/path/to/bootstrap.php"

###2.Require bootstrap

>target files.php

```php
<?php require (dirname($_SERVER['DOCUMENT_ROOT']).'/bootstrap.php'); ?>
<!DOCTYPE html>
<!-- Omitted -->
</html>
```

If you want to match the log line number,It may be set in the configuration.  

>config.php  

```php 
    "line_offset"     => 1,      // for log reporting.  
```

###3.Manualy render. use Front-controller etc.

note: Front-controller is not included in the Tagent  

>config.php  

```php 
    "shutdown_display"   => false,
```

>index.php

```php
<?php
chdir (dirname($_SERVER['DOCUMENT_ROOT']));
require 'vendor/autoload.php';
$agent = \Tagent\Agent::init( require 'config.php' );

/*  resolve $filename by your router  */

$agent->fileDisplay($filename);
```


##Config

> config.php

```php 
<?php
return array();
```

Empty array mean default config.  

> default config.php

```php 
<?php
return array(
    "agent_tag"          => "ag",
    "agent_directories"  => array( "ag/" ),
    "debug"              => false,
    "shutdown_display"   => false,
    "line_offset"        => 0,
    "template_ext"       => ".tpl",
    "log_reporting"      => E_ALL,
    "charset"            => "utf-8",
);
```

    "debug"            => false,

true, Log report to work  

    "log_reporting"    => E_ALL,

E_ERROR | E_WARNING | E_Parse | E_NOTICE | E_DEPRECATED  
'check' log is assigned to E_DEPRECATED  

    "shutdown_display" => true,

true,   `resister_shutdown_function ( Agent->display() )`  

    "agent_tag"        => "ag",

Agent tag is `<ag></ag>`  

    "agent_directories"  => array ("ag/"),

Directory of the modules  
Multiple directories, array('ag/', 'vendor/foo/bar/lib/');  


    "line_offset"      => 0,       // for log reporting.

Adjust line-number for log reporting   

    "template_ext"     => ".tpl",

Template file extension.  


##Agent

Agent object is singleton.  

###method  

```php
// create singleton instance
$agent = \Tagent\Agent::init(require('config.php'));

// get self instance
$agent = Tagent\Agent::self();

//config
$configs = $agent->getConfig();
$config  = $agent->getConfig('key'); 

// Object locator  
$agent->set('name', $mixed, 'module');    // $mixed ... anyone
$agent->set('name', null, 'module');      // unset
$bool = $agent->has('name', 'module');   
$obj = $agent->get('name', 'module');  
$obj = $agent->get('name',);              // default Module='GLOAL' same bellow 

// module variable
$agent->setVariable('key' , 'value' ,'module'); // value is scalar or array or object
$agent->setVariable('key' , null ,'module');    // unset
$value  = $agent->getVariable('key' ,'module');
$values = $agent->getVariable();                 // get all. module 'GLOBAL'
$values = $agent->getVariable(null, 'Foo');      // get all. module 'Foo'

// log     
$agent->log($level,'message', true , 'module' ); // 3rd param is escape ..htmlspecialchars()
$agent->log($level,'message');                   // escape = true, module = 'GLOBAL'

// PDO connection  when first call, connect. then it is keep.
$dbh = $agent->db();            // by config['db']['default']  
$dbh = $agent->db('foobar');    // by config['db']['foobar']
```

##Module control

Agent has the following module elements.  

1. Module instance.  
2. Variables container.  
3. Objects container.  
4. Pulls  
5. Loops  
6. Templates  

When module is opend, module instance is created If a class Module is present.  
When module is closed, also module elements are discarded.  
However, until parse is completed, the module is not closed unless explicitly force close.  


###Open/Close, Current Module transition  

```html
Pre-process start parse, automatically open module 'GLOBAL' 

<html>
  <body>
    <!-- here, 'GLOBAL' current module -->
    <ag Module='Foo'>                  open module 'Foo'
      <!-- here, 'Foo' current module  -->
      <ag Module='Bar' close='yes'>    Force close specify  
        <!-- here, 'Bar' current module =  -->
      </ag>                            Force close module 'Bar'  
      <!-- here, 'Foo' current module  -->
    </ag>                              Not close module 'Foo'.  unless close specify  
    <!-- here, 'GLOBAL' current module -->
  </body>
</html>

Post-process after parse  
 1.call onClose() Module 'Foo'    if instanceof CloseModuleInterface
 2.call onClose() Module 'GLOBAL' if instanceof CloseModuleInterface
 3.close module 'Foo'
 4.close module 'GLOBAL'
```

'GLOBAL' module can not be forced close.  

Open module  

1. If Module class is present, it is instantiated .. class \Module_*module*\Module 
2. The Module Variables & Objects containers are prepared .  

Close module  

1. call Module->onClose() if instanceof CloseModuleInterface.
2. Destruct module instance , if exists it  
   altogether, remove the Module Variables & Objects containers .  


###Module class  

Each module object 'Module.php' is option.  not required.  

The following is also optional 

* extends AbstractModule. for easy access to the module variables, object-locator, log.  
* implements RefreshModuleInterface. for `onRefresh()` call.  
* implements CloseModuleInterface. for `onClose()` call.  

>Module.php

```php 
<?php
/**
 * Module.php
 * namespace Module_Foo   'Foo' is module-name 
 * array $params non-reserved attributes
 * function pull***    pullName
 * function loop***    loopName
 */
namespace Module_Foo;

use Tagent\Agent;
use Tagent\AbstractModule;          // optinal extends
use Tagent\RefreshModuleInterface;  // optinal implements
use Tagent\CloseModuleInterface;    // optinal implements

class Module extends AbstractModule implements RefreshModuleInterface , CloseModuleInterface
{
    public function __construct(array $params)
    {
    }
    public function onRefresh(array $params)
    {
        // RefreshModuleInterface
        // <ag Refresh='on'></ag>
    }
    public function onClose()
    {
        // CloseModuleInterface
        // <ag Close='on'></ag> or post-process after parse
    }
    public function pullBar(array $params)
    {
        // <ag Pull='bar'>
        return array();
    }
    public function loopBaz(array $params)
    {
        // <ag Loop='bar'>
        return array (array());
    }
}
?>
```

`pullBar()` and `loopBaz()` that can also be provided from independent class `\Module_Foo\Pulls\Bar` or `\Module_Foo\Loops\Baz`.

###AbstractModule

#####Variable Operation  
* getVariable($key)  
* setVariable($key, $value)   // < if $vaule==null, unset. >
* setVariablesByArray(array $array)  // < override by array >

Same as next.`\Tagent\Agent::self()->getVariable('key','modulename');`  

#####Object Locator  
* get($name)  
* set($name, $object)  // < if $object==null, unst >
* has($name)  

Same as next.`\Tagent\Agent::self()->get('key','modulename');`  

#####log  
* log($level,'message');

Same as next.`\Tagent\Agent::self()->log('key','modulename',true,'modulename');`

###Pull / Loop

>example

```html
    <ag Module='Foo'>
      <ag Pull='Bar'>{@baz}</ag>  
    </ag>

    <ag Module='Foo'>
      <ag Loop='Qux'>
        <li>{@quux}</li>
      </ag>  
    </ag>
```

>Same 

```html
    <ag Module='Foo' Pull='Bar'>{@baz}</ag>
    <ag Module='Foo' Loop='Qux'>
      <li>{@quux}</li>
    </ag>
```

#####method name  [Pull / Loop]

camelCase and snake-case both available.  

    <ag Pull='Bar'></ag>
    <ag Loop='Bar'></ag>

```
function pullBar(){}  // camelCase
function pull_bar(){} // snake-case

function loopBar(){}  // camelCase
function loop_bar(){} // snake-case
```

    <ag Pull='sub_main'></ag>  //  '_' underscore  
    <ag Loop='sub_main'></ag>  //  '_' underscore  

```
function pullSubMain(){}      // camelCase
function pull\_sub\_main(){}  // snake-case

function loopSubMain(){}      // camelCase
function loop\_sub\_main(){}  // snake-case
```

note : method name is case-insensitive in PHP.  


#####Class file / name [Pull / Loop]

```
config 'agent_directories = array('ag/');

--- Moudle class ---

Module='Foo' Pull='bar'  
Module='Foo' Loop='bar'  
  class file : `/ag/Module_Foo/Module.php`  
  class name : `\Module_Foo\Module`  

--- Independent Pull/Loop class --- 

Module='Foo' Pull='bar'  
  class file : `/ag/Module_Foo/Pulls/Bar.php`  
  class name : `\Module_Foo\Pulls\Bar`  

Module='Foo' Loop='bar'  
  class file : `/ag/Module_Foo/Loops/Bar.php`  
  class name : `\Module_Foo\Loops\Bar`  

Module='Foo' Pull='sub_main'  
  class file : `/ag/Module_Foo/Pulls/Sub/Main.php`  
  class name : `\Module_Foo\Pulls\Sub_Main.php`  

Module='Foo' Loop='sub_main'  
  class file : `/ag/Module_Foo/Loops/Sub/Main.php`  
  class name : `\Module_Foo\Loops\Sub_Main.php`  
```

note : Tagent\ModuleLoader is compatible psr-0.  convert from '\_' to DIRECTORY\_SEPALATOR in class name.  

#####Priority order of serarch method  [Pull/Loop]


1. `Module_Foo\Module->pullBar($params)` instance method
2. `Module_Foo\Module->pull_bar($params)` instance method
3. `Module_Foo\Pulls\Bar::pullBar($params)` class static method
4. `Module_Foo\Pulls\Bar->pullBar($params)` instance method
5. `Module_Foo\Pulls\Bar::pull_bar($params)` class static method
6. `Module_Foo\Pulls\Bar->pull_bar($params)` instance call
7. `Module_Foo\Pulls\Bar($params)` instance __invoke() call


>Module_Foo\Module.php  Module class method

```php
<?php
namespace Module_Foo;

class Module extends AbstractModule
{
    // pull_bar($params) also available snake-case 
    public function pullBar(array $params)
    {
        return array('foo'=>'FOO', 'bar'=>'BAR' );
    }
}
?>```

>Module_Foo\Pulls\Bar.php   static method

```php
<?php
namespace Module_Foo\Pulls;

use Tagent\Agent;
use Tagent\AbstractModule;

class Bar extends AbstractModule
{
    // pull_bar($params) also available snake-case 
    public static function pullBar(array $params)
    {
        return array('foo'=>'FOO', 'bar'=>'BAR' );
    }
} 
?>
```

>Module_Foo\Pulls\Bar.php  instansable class method

```php
<?php
namespace Module_Foo\Pulls;

use Tagent\Agent;
use Tagent\AbstractModule;

class Bar extends AbstractModule
{
    // pull_bar($params) also available snake-case 
    public function pullBar(array $params)
    {
        return array('foo'=>'FOO', 'bar'=>'BAR' );
    }
} 
?>
```

>Module_Foo\Pulls\Bar.php   instansable and invokable class

```php
<?php
namespace Module_Foo\Pulls;

use Tagent\Agent;
use Tagent\AbstractModule;

class Bar extends AbstractModule
{
    public function __invoke(array $params)
    {
        return array('foo'=>'FOO', 'bar'=>'BAR' );
    }
} 
?>
```

$params are non-reserved attributes.  

####Pull Return 

Array or Object  

```php   
<?php
return array('foo'=>'FOO', 'bar'=>'BAR' );
// or
return (object) array('foo'=>'FOO', 'bar'=>'BAR' );
?>
```

>template 

```html
<ag Module='Foo' Pull='Bar'>
{@foo}-{@bar}
</ag>
```

>output

```
FOO-BAR
```

###loop

`<ag Module='Foo' Loop='Baz'></ag>`  

>Module_Foo\Module.php  instansable class method

```php
<?php
namespace Module_Foo;

class Module extends AbstractModule
{
    // loop_baz($params) also available snake-case 
    public function loopBaz(array $params)
    {
        return array( array('foo'=>'FOO', 'bar'=>'BAR' ));
    }
}
?>```

>Module_Foo\Loops\Baz.php   static method

```php
<?php
namespace Module_Foo\Loops;

use Tagent\Agent;
use Tagent\AbstractModule;

class Baz extends AbstractModule
{
    // loop_baz($params) also available snake-case 
    public static function loopBaz(array $params)
    {
        return array( array('foo'=>'FOO', 'bar'=>'BAR' ));
    }
} 
?>
```

>Module_Foo\Loops\Baz.php  instansable class method

```php
<?php
namespace Module_Foo\Loops;

use Tagent\Agent;
use Tagent\AbstractModule;

class Baz extends AbstractModule
{
    // loop_baz($params) also available snake-case 
    public function loopBaz(array $params)
    {
        return array( array('foo'=>'FOO', 'bar'=>'BAR' ));
    }
} 
?>
```

>Module_Foo\Loops\Baz.php   instansable and invokable class

```php
<?php
namespace Module_Foo\Loops;

use Tagent\Agent;
use Tagent\AbstractModule;

class Baz extends AbstractModule
{
    public function __invoke(array $params)
    {
        return array( array('foo'=>'FOO', 'bar'=>'BAR' ));
    }
} 
?>
```

$params are non-reserved attributes.  

####Loop Return 

Array(Array()) or Traversable Object

```php
<?php
namespace Module_Foo;
class Module
{
  public function loopBaz($params){
    // return array
    return array(
        array('id'=>'001', 'name'=>'John'),
        array('id'=>'002', 'name'=>'Jane'),
        array('id'=>'003', 'name'=>'paul'),
      );
    // pdo object
    $pdo = new \PDO($dsn, $user, $pass); 
    return $pdo->query("select id,name from users"); // return PDOstatment object that is traversable

    // pdo object use agent::db()     see DB section
    $agent = Agent::self();
    $db = $agent->db();
    return $db->query("select id,name from users"); 
  }
}
?>
```

```html
<ag Module='Foo' Loop='Baz'>
<li>{@l:id}-{@l:name}</li>
</ag>
```

>output

```html
<li>001-john</li>
<li>002-jane</li>
<li>003-paul</li>
```

#####Loop Return example  see {@LOOPKEY}

```php
<?php
namespace Module_Foo;
class Module
{
  public function loopBaz($params){
     $array['apple'] = array ('article' => 'An',  'color' => 'red'    );
     $array['sun']   = array ('article' => 'The', 'color' => 'yellow' );
     $array['sky']   = array ('article' => 'The', 'color' => 'blue'   );
     return $array;
  }
}
?>
```

>template

```html
<ul>
  <ag Module='Foo' loop='Baz'>
  <li>{@l:article} {@l:LOOPKEY} is {@l:color}</li>
  </ag>
</ul>
```

>output

```html
<ul>
  <li>An apple is red</li>
  <li>The sun is yellow</li>
  <li>The sky is blue</li>
</ul>
```

####Loop Return example return empty array as display toggle

>template

```html
<div>
  <ag Module='Foo' loop='Baz' switch='off'>
    <!-- content -->
    <p><ag Template='Content'></ag></p>
  </ag>
</div>
```

```php
<?php
public function loopBaz($params){
  if ($params['switch'] == 'off') {
    return array();  // empty array
  } else {
    return array(array());
  }
}
?>
```

>output

```html
<div>
</div>
```


##Object Locator

###Agent method  

`$agent->set('name', $object, $module)`  
`$agent->get('name', $module)`  
`$agent->has('name', $module)`  

If the module name is omitted, it defaults to GLOBAL.  

###AbstractModule Object  

`$Module->get('name', $object)`  
`$Module->get('name')`  
`$Module->get('name')`  

Module name is resolved in the namespace of the class that inherits from abstractModule.  
Therefore, a class that inherits abstractModule, it is necessary to always Module _ *** \ following namespace.  

###AbstractModule  

>Module.php  

```php
<?php
namespace Module_Foo;

class Module extends AbstractModule
{
  public function bar()
  {
    $this->set('baz', new anyclass );
    $obj = $this->get('baz');
  }
}
```

###FactoryInterface

implements FactoryInterface  


>Module.php

```php
<?php
namespace Module_Foo;

class Module extends AbstractModule
{
  public function __construct($params) {
    $this->set('baz', new anyclass($params);
  }
  public function pull_bar($params) {
    return $this->get('baz')->();
  }
}
```

```php
<?php
class someclass implements FactoryInterface
{
  protected $property;
  public function __construct($params) {
    $this->property = $params;
  }
  public function factory() {
    return new baz($this->property);
  }
}
```

##DB

get PDO connection by Config setting.

>config.php

```php
<?php
return array(
    "db" => array(
        'default'=> array (
            "dsn"      => "mysql:host=localhost;dbname=test",
            "user"     => "username",
            "password" => "password",
            "options"  => array(\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC),
        ),
        'foobar'=> array (
            "dsn"   => "sqlite:db/testdb.sqlite3",
            // user=''; password=''; options=array();
        )
    )
);
```

>usage

```php
<?php

$dbh_default = \Tagent\Agent::self()->db();          // get PDO object by config ['db']['default']

$dbh_foo     = \Tagent\Agent::self()->db('foobar');     // get PDO object by config ['db']['foo']
```


##Autoloader

class ModuleLoader  

Config-key 'agent_directories' = array( 'ag/' ) // default  
Target namespace is \Module_\*\*\*  


1. Class \Module\_Foo\Module  
require ag/Module\_Foo/Module.php  

2. Class \Module\_Foo\Pulls\bar  
require ag/Module\_Foo/Pull/bar.php  

3. Class \Module\_Foo\Classes\Common\_Baz  
require ag/Module\_Foo/Classes/Common/Baz.php  

**psr-0 compatible**  

clasname '\_' replace to `DIRECTORY_SEPALATOR`.    
case-sensitive.  
