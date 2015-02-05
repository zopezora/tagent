
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
`module`,`method`,`loop`,`parse`,`close`,`refresh`,`newmodule`  

Other attributes are used as a property  array $params  (see. Module,method,loop)  

###Exmaples

> template

```html
<?php require (dirname($_SERVER['DOCUMENT_ROOT']).'/bootstrap.php'); ?>
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

> json in script jQuery

```html
<?php require (dirname($_SERVER['DOCUMENT_ROOT']).'/bootstrap.php'); ?>
<script>
  var obj = $.parseJSON('{@some|json}');
</script>
```

##Directory

example : use composer  

```
[project] - [public] - WEB DOCUMENT ROOT
          |            index.php
          |            (-some-)
          |- [ag]-[Module_GLOBAL] -[Methods] - *.php 
          |      |                 [Loops]   - *.php
          |      |                 [-some-]  - *.php
          |      |                 Module.php
          |      |
          |      |-[Module_Foo]----[Methods] - *.php
          |                        [Loops]   - *.php
          |                        [-some-]  - *.php
          |                        Module.php
          |- bootstrap.php
          |- config.php
          |- [vendor]-[tagent]-[tagent]-[lib]-Agent.php
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
    "debug"           => true,
    "ob_start"        => true,
    "agent_tag"       => "ag",
    "agent_directory" => "ag/",
    "line_offset"     => 1,
);
?>
```

##Module control

Agent has the following module elements.  

1. Module instance.  
2. Module Variables.  
3. Module Object.  

When module be closed, also module elements are discarded.  
However, until parse is complete, the module is not closed unless explicitly.  


###Open/Close, Current Module transition  

```html
start parse, automatically open module 'GLOBAL' 

--- here, 'GLOBAL' current module ---

<ag module='Foo'>   open module 'Foo'  try module instance , and construct module elements.  

  --- here, 'Foo' current module  ---

  <ag module='Bar' close='yes'>   explicitly force close  

       --- here, 'Bar' current module =  ---

  </ag>  force close module 'Bar'  

  --- here, 'Foo' current module  ---

</ag>  note: Not close module 'Foo'.


after pearse compleate 
1.close module 'Foo'
2.close module 'GLOBAL'
```

<ag method='bar'></ag>   The specified method of the current module.  

###Module object  

Each module object 'Module.php' is option.  not required.  

Also option below.  

* extends AbstractModule. Easy access to the module variables  
* implements RefreshModuleInterface. instance of this interface,  

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
* getVariable($key = null, $modulename = null)  
* setVariable($key, $value, $modulename = null)  
* setVariablesByArray(array $array, $modulename = null)  

#####Object Locator  
* get($name , $modulename = null)  
* set($name, $object, $modulename = null)  
* has($name, $modulename = null)  

Same as next.`\Tagent\Agent::getInstance()->getVariable('key,'modulename');`

hoeever...  
If you omit 2nd parameter $modulename, It is complemented by namespace of self class name .  
ex. class Module_Foo\name  , automaticaly set modulename = 'Foo'  
So easy to use  
`$this->getVariable('key')` , `$this->get('key')`  etc...  


###method

`<ag module='Foo' method='bar'></ag>`

First ,  seach `function method_bar()` in module object \Module_Foo\Module.  
Second , search `class \Module_Foo\Methods\bar`  

>Module_Foo\Methods\bar.php

```php
<?php
namespace Module_Foo\Methods;

use Tagent\Agent;
use Tagent\AbstractModule;

class bar extends AbstractModule
{
    public function bar(array $params){
      return array();
    }
} 
?>
```

if not exist function bar and is_callable, then call __invoke($params)  

example. return array['apple']='red'; ,  use {@apple}  

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
    public function baz(array $params){
      return array(array());
    }
} 
?>
```

if not exist function baz and is_callable, then call __invoke($params)  

####Example_1.  return array( array() )

```php
     $array[1]['color'] = 'Red';   
     $array[2]['color'] = 'Blue';  
     $array[3]['color'] = 'White';  
     return $array;
```

>template
  
    <ag loop='baz'><li>{@LOOPKEY}-{@color}</li></ag>

>output

    <ul><li>1-Red</li><li>2-Blue</li><li>3-White</li></ul>



####Example_2  return empty array.  Available as display toggle

    return array();

>template
  
    <ul><ag loop='baz'><li>{@LOOPKEY}-{@color}</li></ag></ul>

>output

    <ul></ul>

##Autoloader

class ModuleLoader  

Config-key 'agent_directory' ( default : 'ag/'  )

Namespace \Module_***    


1. Classname Module_Foo\Module  
require ag/Module_Foo/Module.php  

2. Classname Module_Foo\Methods\bar  
require ag/Module_Foo/Methods/bar.php  

3. Classname Module_Foo\Classes\Common_Baz
require ag/Module_Foo/Classes/Common_Baz.php  

note:'_' no replace. case-sensitive.


