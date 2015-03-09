-------------
# **Under review**

-------------

# tagent

PHP template parser.  

##Features

* Tiny PHP Freamework for PHP non-full-stack site.
* View-driven Model. Just in time.
* Tag style templates. Compatibility with web design skill and web tools.
* Module control for reusability.
* Object Locator for sharing between modules.
* Embedded external template.  
* Template inheritance using buffer.
* User defined filters.  
* Html log reporting for debug  

##Template  

> example  

```html
<html>
  <head>
    <ag>
      <title>{@title}</title>
    </ag>
    <ag module='Foo'>
      <script>
        var obj = parseJSON('{@obj|json}');
      </script>
    </ag>
  </head>
  <body>
    <h1><ag>{@title}</ag></h1>
    <ag Module='Bar'>
      <ul>
        <ag Loop='Baz'>
        <li>{@l:id|f'%05d'}:{@l:name|h}</li>
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
* case-sensitive.  First letter of reserved attribute are upper-case.  


####Variable

```text
{@scope:variable|filter}
```

* scope:(optional)  
`loop`/`l` , `module`/`m` , `global`/`g`  

* filter:(optional)  
`html`/`h` , `raw`/`r` ,`url`/`u` , `json`/`j` ,`base64`/`b`  
printf filter  
`f'format'`  ex. `f'%05d'`  
arithmetic operation filter  
`+`,`-`,`*`,`/`,`^`  ex. `+1`,`-2.3`  

It is possible to add user-defined filters.  

- - - - - - -
###Document

[Development memo](docs/Document.md)

- - - - - - -

#####Todo now

* Testing
