-------------
# **Under review**

-------------

# tagent

PHP template parser.  

##Features

* Tiny PHP Freamework for non full PHP site.
* View-driven Model  
* Tag template parser  
* Module control  
* Object Locator  

##Template  

###Parse target

* Tag .......... `<ag></ag>`  
* Variable ... `{@VARIABLE}`  

####Tag
```text
<ag ATTRIBUTE=VALUE>{@VARIABLE}</ag>
```

Reserved attributes are following   
`module`,`pull`,`loop`,`parse`,`close`,`refresh`,`newmodule`,`template`,`check`,`debug`,`header`  

Other attributes are used as a property array $params  (see. Module,pull,loop)  

####Variable
```text
{@scope:name|format}
   ex.{@foo}, {@m:foo}, {@foo|r},  {@global:foo|json}
```

* scope(option)  
`loop` or `l`  
`module` or `m`  
`global` or `g`  
case-insensitive

* name  
/\w+/  
a to z, A to Z. and '_' under bar  

* format(option)  
`html` or `h`  (default)  htmlspecialchars()  
`raw` or `r`  
`url` or `u`  urlencode()  
`json` or `j`  json_encode()  
case-insensitive

###Exmaples

> template

```html
<h1>{@title}</h1>
<ag module='Foo'>
 <p>{@bar}</p>
 <ul>
   <ag loop='baz'>
     <li>{@l:item}</li>
   </ag>
 </ul>
</ag>
```

> json in html script jQuery.parse  

```html
<ag module='Foo'>
<script>
  var obj = $.parseJSON('{@bar|json}');
</script>
</ag>
```

> json in script jQuery  .js  

```js
/* <ag header ='Content-Type: application/javascript; charset=utf-8'></ag>
   <ag module='Foo'> */
  var obj = $.parseJSON('{@bar|json}');
/* </ag> */
```

> json.php  

```text
<ag header ='Content-Type: application/json; charset=utf-8' module='Foo'>{@bar|json}</ag>
```

> css.php

```text
/* <ag header='Content-Type: text/css; charset=utf-8'></ag> 
   <ag module='Foo' pull='bar' border='1px solid #aaa'> */
div.bar {
  border : {@border};
}
div.baz {
  border : {@border};
}
/* </ag> */
```

##Basic Directory Structure 

use composer 

```
[project] - [public] - WEB DOCUMENT ROOT
          |            index.php
          |            (-some-)
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

###Fixed directory & filename  

| Directory      | Filename   |Class                   | TAG attribute                           |
|----------------|------------|------------------------|-----------------------------------------|
|[Module\_Foo]   |Module.php  |\Module\_Foo\Module     |`<ag module='Foo'></ag>`                 |
|[Pulls]         |bar.php     |\Module\_Foo\Pulls\bar  |`<ag module='Foo' pull='bar'></ag>`      |
|[Loops]         |baz.php     |\Module\_Foo\Loops\baz  |`<ag module='Foo' loop='baz'></ag>`      |
|[Templates]     |qux.tpl     | --- no class ---       |`<ag module='Foo' template='qux'></ag>`  |


###Fixed method name  

| Method     | Class                    |TAG attribute                         |
|------------|--------------------------|--------------------------------------|
|onRefresh() |\Module\_Foo\Module       |`<ag module='Foo' refresh='on'></ag>` |
|onClose()   |\Module\_Foo\Module       |`<ag module='Foo' close='on'></ag>`   |
|pull\_bar() |\Module\_Foo\Module       |`<ag module='Foo' pull='bar'></ag>`   |
|pull\_bar() |\Module\_Foo\Pulls\bar    |`<ag module='Foo' pull='bar'></ag>`   |
|loop\_baz() |\Module\_Foo\Module       |`<ag module='Foo' loop='baz'></ag>`   |
|loop\_baz() |\Module\_Foo\Loops\baz    |`<ag module='Foo' loop='baz'></ag>`   |

* onRefresh() ... RefreshModuleInterface
* onClose()   ... CloseModuleInterface


>bootstrap.php  example 

```php 
<?php
chdir (__DIR__);
require 'vendor/autoload.php';
$agent = \Tagent\Agent::init( require 'config.php' );
```

>config.php example 

```php 
<?php
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

    AddType application/x-httpd-php .tpl
    php_value auto_prepend_file "/path/to/bootstrap.php"
  
  

*carefully. There are several risks*  

.html .js .css and .php(default)  

>.htaccess  

    AddType application/x-httpd-php .tpl .html .js .css
    php_value auto_prepend_file "/path/to/bootstrap.php"

###2.Require bootstrap

>targetfiles.php

    <?php require (dirname($_SERVER['DOCUMENT_ROOT']).'/bootstrap.php'); ?>
    <!DOCTYPE html>
    <!--- omission --->
    </html>

If you want to match the log line number,It may be set in the configuration.  

>config.php  

```php 
    "line_offset"     => 1,      // for log reporting.  
```

###3.Front-controller

Front-controller is not included in the Tagent  

>index.php

```php
<?php
chdir (__DIR__);
require 'vendor/autoload.php';
$agent = \Tagent\Agent::init( require 'path/to/config.php' );

// ---  resolve $filename by your router ---

$agent->fileDisplay($filename);
```

>config.php  

```php 
    "shutdown_display"   => false,
```

##Config

```php 
<?php
return array(
    "debug"             => false,
    "log_reporting"     => E_ALL,
    "shutdown_display"  => true,
    "agent_tag"         => "ag",
    "agent_directories" => array("ag/"),
    "line_offset"       => 0,
    "template_ext"      => ".tpl",
);
```

    "debug"            => false,

true, Log report to work  

    "log_reporting"    => E_ALL,

E_ERROR | E_WARNING | E_PARSE | E_NOTICE | E_DEPRECATED  
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
$obj = $agent->get('name',);              // default module='GLOAL' same bellow 

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
    <ag module='Foo'>                  open module 'Foo'
      <!-- here, 'Foo' current module  -->
      <ag module='Bar' close='yes'>    Force close specify  
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


###Module object  

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
 * function pull_***    pull-name
 * function loop_***    loop-name
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
        // <ag refresh='on'></ag>
    }
    public function onClose()
    {
        // CloseModuleInterface
        // <ag close='on'></ag> or post-process after parse
    }
    public function pull_bar(array $params)
    {
        // <ag pull='bar'>
        return array();
    }
    public function loop_baz(array $params)
    {
        // <ag loop='bar'>
        return array (array());
    }
}
```

`pull_bar()` and `loop_baz()` that can also be provided from independent class `\Module_Foo\Pulls\bar` or `\Module_Foo\Loops\baz`.

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

###pull

ex.  

    <ag module='Foo'>
      <ag pull='bar'>{@baz}</ag>  
    </ag>

same 

    <ag module='Foo' pull='bar'>{@baz}</ag>


First ,  seach method `function pull_bar()` in module object \Module_Foo\Module.  
Second , search `class \Module_Foo\Pulls\bar`  , call `pull_bar()` or __invoke()  

fix namespace and directory.

>Module_Foo\Pulls\bar.php

```php
<?php
namespace Module_Foo\Pulls;

use Tagent\Agent;
use Tagent\AbstractModule;

class bar extends AbstractModule
{
    public function pull_bar(array $params){
      return array();
    }
} 
```

call function `pull_bar()` or call __invoke($params)  
$params are non-reserved attributes.  

return example. return array['apple']='red'; ,set pull variable  {@apple}  

###loop

`<ag module='Foo' loop='baz'></ag>`

First , seach `function loop_baz()` in module object \Module_Foo\Module.  
Second , search `class \Module_Foo\Loops\baz`  

>Module_Foo\Loops\baz.php

```php
<?php
namespace Module_Foo\Loops;

use Tagent\Agent;
use Tagent\AbstractModule;

class baz extends AbstractModule;
{
    public function loop_baz(array $params){
      return array(array());
    }
} 
```

call function `loop_baz()` or call __invoke($params)  
$params are non-reserved attributes.  

####return example 1.array( array() )

```php
     $array[1]['color'] = 'Red';   
     $array[2]['color'] = 'Blue';  
     $array[3]['color'] = 'White';  
     return $array;
```

>template
  
    <ul><ag loop='baz'><li>{@LOOPKEY}-{@color}</li></ag></ul>

>output

    <ul><li>1-Red</li><li>2-Blue</li><li>3-White</li></ul>



####return example 2  return empty array.  Available as display toggle

    return array();

>template
  
    <ul><ag loop='baz'><li>{@LOOPKEY}-{@color}</li></ag></ul>

>output

    <ul></ul>

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

2. Class \Module\_Foo\Methods\bar  
require ag/Module\_Foo/Methods/bar.php  

3. Class \Module\_Foo\Classes\Common\_Baz  
require ag/Module\_Foo/Classes/Common/Baz.php  

**psr-0 compatible**  

clasname '\_' replace to `DIRECTORY_SEPALATOR`.    
case-sensitive.  
