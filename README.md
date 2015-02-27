-------------
# **Under review**

-------------

# tagent

PHP template parser.  

##Features

* Tiny PHP Freamework for PHP non-full-stack site.
* View-driven Model. Just in time.
* Tag style template parser. Compatibility with web design skill and web tools.
* Module control for versatility.
* Object Locator for sharing between modules.
* Embedding template by store and restore to the buffer

##Template  

> example  

```html
<html>
  <body>
    <h1><ag>{@title}</ag></h1>
    <ag Module='Foo'>
      <p>{@bar|r}</p>
      <ul>
        <ag Loop='Baz'>
          <li>{@id}:{@l:name}</li>
        </ag>
      </ul>
    </ag>
  </body>
</html>
```

###Parse target

* Tag ......... `<ag></ag>`  
* Variable ... `{@VARIABLE}`  The inside of the `<ag></ag>` only

####Tag

```text
<ag ATTRIBUTE=VALUE></ag>
```

#####Reserved attributes

`Module`,`Pull`,`Loop`,`Parse`,`Close`,`Refresh`,`Reopen`,`Template`,`Check`,`Debug`,`Header`,`Read`,`Trim`,`Store`,`Restore`  

####Variable

```text
{@scope:variable|format}
```

scope:  `loop`/`l` , `module`/`m` , `global`/`g`  

format `html`/`h` , `raw`/`r` ,`url`/`u` , `json`/`j` ,`base64`/`b`  

- - - - - - -

#####Todo now

* User define format injection. callable example clouser.  change name format to filter/modificator/processor?  
about:   $agent->setFormat('full-name', 'short', function($str){ return $str;});

* add string type format use sprintf()
about: {@var|'%05d'|h}

* shape up / cost tune Agent::fetch()

* config method name default case. (switch camel or snake)  

* functional variable   {@function('foo',...)} or {#function('foo',...)} etc  nnn logical?

