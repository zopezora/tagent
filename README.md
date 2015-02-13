
#===!!! DRAFT !!!===  

It is under consideration.  
I will change on a whim.  

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

###parse target

* `<ag></ag>`  
* `{@VARIABLE}`  

```text
<ag ATTRIBUTE=VALUE>{@VARIABLE}</ag>
```

some attributes are reserved.  
`module`,`method`,`loop`,`parse`,`close`,`refresh`,`newmodule`,`template`,`check`,`debug`,`header`  

Other attributes are used as a property  array $params  (see. Module,method,loop)  

###Exmaples

> template

```html
<h1>{@title}</h1>
<ag module='Foo'>
 <p>{@bar}</p>
 <ul>
   <ag loop='baz'>
   <li>{@item}</li>
   </ag>
 </ul>
</ag>
```

> json in script jQuery  html <script></script>

```html
<ag module='Foo'>
<script>
  var obj = $.parseJSON('{@some|json}');
</script>
</ag>
```

> json in script jQuery  .js  

```js
// <ag module='Foo'>
  var obj = $.parseJSON('{@some|json}');
//</ag>
```

> css.php

```css
/* <ag header='Content-Type: text/css; charset=utf-8'><> */
/* <ag module='Foo' method='bar' border='1px solid #aaa'> */
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
          |- [ag]-[Module_GLOBAL] -[Methods]  - *.php 
          |      |                 [Loops]    - *.php
          |      |                 [Templates]- *.tpl
          |      |                 [......]   - *.php
          |      |                 Module.php
          |      |
          |      |-[Module_Foo]----[Methods]  - *.php
          |                        [Loops]    - *.php
          |                        [Templates]- *.tpl
          |                        [......]   - *.php
          |                        Module.php
          |- bootstrap.php
          |- config.php
          |- [vendor]-[tagent]-[tagent]-[lib]-Agent.php
                                              (......)
```

>bootstap.php

```php 
<?php
chdir (__DIR__);
require 'vendor/autoload.php';
$agent = \Tagent\Agent::init( require 'config.php' );
?>
```

>config.php

```php 
<?php
return array(
    "debug"            => false,
    "shutdown_display" => true,
    "agent_tag"        => "ag",
    "agent_directory"  => "ag/",
    "line_offset"      => 0,
    "template_ext"     => ".tpl",
    "log_reporting"    => E_ALL,
);
?>
```

##How to boot

several ways   

1. Preload bootstrap.php in .htaccess.    (Recommended)  
1. require in the first line of each file   (Easy quick start)  
3. Use front-controller                   (Controller is not included)  


###1.Preload bootstrap.php

by .htaccess  

automatically preload bootstrap.php for file extension .tpl and .php(default)  

>.htaccess  

    AddType application/x-httpd-php .tpl
    php_value auto_prepend_file "/path/to/bootstrap.php"
  
  

*carefully. There are several risks*  

.html .js .css and .php(default)  

>.htaccess  

    AddType application/x-httpd-php .tpl .html .js .css
    php_value auto_prepend_file "../bootstrap.php"

###2.require bootstrap

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
$agent = \Tagent\Agent::init( require 'config.php' );

// ---  resolve $filename by your router ---

$agent->fileDisplayFile($filename);
?>
```

##Config

```php 
<?php
return array(
    "debug"            => false,
    "log_reporting"    => E_ALL,
    "shutdown_display" => true,
    "agent_tag"        => "ag",
    "agent_directory"  => "ag/",
    "line_offset"      => 0,
    "template_ext"     => ".tpl",
);
?>
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

    "agent_directory"  => "ag/",

Location of the module  

    "line_offset"      => 0,       // for log reporting.

Adjust line-number for log reporting   

    "template_ext"     => ".tpl",

Template file extension.  


##Module control

Agent has the following module elements.  

1. Module instance.  
2. Variables container.  
3. Objects container.  
4. Methods  
5. Loops  
6. Templates  

When module is opend, module instance is created If a class module is present.  
When module is closed, also module elements are discarded.  
However, until parse is completed, the module is not closed unless explicitly force close.  


###Open/Close, Current Module transition  

```html
start parse, automatically open module 'GLOBAL' 

--- here, 'GLOBAL' current module ---

<ag module='Foo'>   open module 'Foo'

  --- here, 'Foo' current module  ---

  <ag module='Bar' close='yes'>   explicitly force close  

       --- here, 'Bar' current module =  ---

  </ag>  force close module 'Bar'  

  --- here, 'Foo' current module  ---

</ag>  note: Not close module 'Foo'.  unless close attribute

--- here, 'GLOBAL' current module ---

after pearse compleate 
1.close module 'Foo'
2.close module 'GLOBAL'
```

'GLOBAL' module can not be forced close.  

Open module  

1. If Module class is present, it is instantiated .. class \Module_*module*\Module 
2. The Module Variables & Objects containers are prepared .  

Close module  

1. Destruct module instance , if exists it  
2. Remove the Module Variables & Objects containers .  


ex.  

    <ag module='Foo'>
      <ag method='bar'></ag>  
    </ag>

same 

    <ag module='Foo' method='bar'></ag>


###Module object  

Each module object 'Module.php' is option.  not required.  

Also option below.  

* extends AbstractModule. Easy access to the module variables & log  
* implements RefreshModuleInterface. for action module 'refresh'.  

>Module.php

```php 
<?php
/**
 * Module.php
 * namespace Module_***   module-name 
 * array $params non-reserved attributes
 * function method_***    method-name
 * function loop_***      loop-name
 */
namespace Module_Foo;

use Tagent\Agent;
use Tagent\AbstractModule;
use Tagent\RefreshModuleInterface;

class Module extends AbstractModule implements RefreshModuleInterface
{
    public function __construct(array $params)
    {
    }
    public function refresh(array $params)
    {
        // RefreshModuleInterface
        // <ag refresh='on'></ag>
    }
    public function method_bar(array $params)
    {
        // <ag method='bar'>
        return array();
    }
    public function loop_baz(array $params)
    {
        // <ag loop='bar'>
        return array (array());
    }
}
?>
```

###AbstractModule

#####Variable Operation  
* getVariable($key)  
* setVariable($key, $value)   // < if $vaule==null, unset. >
* setVariablesByArray(array $array)  // < override by array >

Same as next.`\Tagent\Agent::getInstance()->getVariable('key,'modulename');`  

#####Object Locator  
* get($name)  
* set($name, $object)  // < if $object==null, unst >
* has($name)  

Same as next.`\Tagent\Agent::getInstance()->get('key,'modulename');`  

#####log  
* log($level,'message');

Same as next.`\Tagent\Agent::getInstance()->log('key,'modulename',true,'modulename');`

###method

`<ag module='Foo' method='bar'></ag>`

First ,  seach method `function method_bar()` in module object \Module_Foo\Module.  
Second , search `class \Module_Foo\Methods\bar`  , call `method_bar()`  

fix namespace and directory.

>Module_Foo\Methods\bar.php

```php
<?php
namespace Module_Foo\Methods;

use Tagent\Agent;
use Tagent\AbstractModule;

class bar extends AbstractModule
{
    public function method_bar(array $params){
      return array();
    }
} 
?>
```

call function `method_bar()` or call __invoke($params)  
$params are non-reserved attributes.  

return example. return array['apple']='red'; ,set method variable  {@apple}  

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
?>
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
  public function bar() {
    $this->set('some', new someclass;
    $this->get('some')->somemethod();

  }
}
?>
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
    $this->set('some', new someclass($params);
  }
  public function method_bar($params) {
    return $this->get('some')->someMethod();
  }
}
?>
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
?>
```

##Autoloader

class ModuleLoader  

Config-key 'agent_directory' ( default : 'ag/'  )

Namespace \Module_***    


1. Classname Module_Foo\Module  
require ag/Module_Foo/Module.php  

2. Classname Module_Foo\Methods\bar  
require ag/Module_Foo/Methods/bar.php  

3. Classname Module_Foo\Classes\Common_Baz
require ag/Module_Foo/Classes/Common/Baz.php  

**psr-0 compatible**  
clasname '_' replace to `DIRECTORY_SEPALATOR`.    
case-sensitive.  
