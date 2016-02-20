# AttributedString
A class to work with attributed strings in PHP. Attributed strings are strings that can have multiple attributes per character of the string. Each attribute is a bitfield (boolean array) with the length of the string. This simple data structure can be used to implement lots of interesting things like:
 * text-decorations, colors, fonts etc in a word processor (e.g. set a range of the string to have the "bold" attribute)
 * semantic text analysis systems (with attributes like "verb" and "noun")
 * core text extraction

## Examples
```
  use apemsel\AttributedString\AttributedString;
  
  // ...
  
  $as = new AttributedString("The quick brown fox");
  
  $as->setLength(10, 5, "color"); // "brown" has attribute "color"
  $as->is("color", 12) // == true
  
  $as->setPattern("/[aeiou]/", "vowel"); // vowels have attribute "vowel"
  $as->getAttributes(12) // char at offset 12 has attributes ["color", "vowel"]
  
```
 
## Versions
### dev-0.0.1
* very basic implementation
