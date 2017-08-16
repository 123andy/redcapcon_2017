

// Proxy function openAddQuesForm(sq_id,question_type,section_header,signature) {
```javascript
 $(document).ready( function() {
     // Override the redcap_validate with an anonymous function
     (function () {
         // Cache the original function under another name
         var proxied = redcap_validate;
         // Redefine the original function
         redcap_validate = function () {
             // Examine the arguments to this function so you can do your own thing:
             console.log('Arguments are:', arguments);

             // Get the element that is being validated
             var element = arguments[0];
             console.log ("Element is " + $(element).attr('id'));
             var value = $(element).val();
             console.log ("Value is " + value);

             // Do you want to override the normal function?
             if ( value == 'foo' ) {
                 // do my override and return the result
                 $result = false; // override_stuff;
             } else {
                 // Do the original proxied function
                 $result = proxied.apply(this, arguments);
             }
             return $result;
         }
     })()
 });
```
