# AttributedString
A class to work with attributed strings in PHP. Attributed strings are strings that can have multiple attributes per character of the string. Each attribute is a bitfield (boolean array) with the length of the string. This simple data structure can be used to implement lots of interesting things like:
 * text-decorations, colors, fonts etc in a word processor (e.g. set a range of the string to have the "bold" attribute)
 * semantic text analysis systems (with attributes like "verb" and "noun")
 * core text extraction

## Examples
```php
  use apemsel\AttributedString\AttributedString;
  
  // ...
  
  $as = new AttributedString("The quick brown fox");
  
  $as->setLength(10, 5, "color"); // "brown" has attribute "color"
  $as->is("color", 12); // == true
  $as->toHtml(); // "The quick <span class=\"color\">brown</span> fox"
  
  $as->setPattern("/[aeiou]/", "vowel"); // vowels have attribute "vowel"
  $as->getAttributes(12); // char at offset 12 has attributes ["color", "vowel"]
  
  $as->combineAttributes("and", "color", "vowel", "colored-vowel"); // also use "or", "not", "xor" to combine attributes
  $as->is("colored-vowel", 12); // "o" of "brown" is a color vowel ;-)

  $as->setSubstring("fox", "noun"); // all instances of "fox" have attribute "noun"
  $as->is("noun", 16); // true, char at offset 16 is part of a noun
  
  $as->searchAttribute("vowel"); // 2, first vowel starts at offset 2
  $as->searchAttribute("vowel", 0, true); // [2, 1], first vowel starting at offset 0 is at offset 2 with length 1
  
  // MutableAttributedString can be modified after creation and tries to be smart about the attributes
  $mas = new MutableAttributedString("The brown fox");
  $mas->setLength(0, 13, "bold");
  $mas->insert(4, "quick "); // "The quick brown fox";
  $mas->is("bold", 6) // true, "quick" is now also bold since the inserted text was inside the "bold" attribute
  $mas->delete(10, 6) // "The quick fox"
  
  // TokenizedAttributedString tokenizes the given string, can set attributes by token
  // and maintains the tokens' offsets in the original string.
  $tas = new TokenizedAttributedString("The quick brown fox"); // tokenize using the default whitespace tokenizer
  $tas->getToken(2); // "brown"
  $tas->setTokenAttribute(2, "bold"); // "brown" is "bold"
  $tas->getTokenOffset(2); // 10, "brown" starts at offset 10
  $tas->getTokenOffsets(); // [0, 4, 10, 16], start offsets of the tokens in the string
  $tas->setTokenRangeAttribute(2, 3, "underlined"); // set tokens 2 to 3 to "underlined"
  $tas->getAttributesAtToken(2); // ["bold", "underlined"]
  $tas->lowercaseTokens(); // convert tokens to lowercase
  $tas->setTokenDictionaryAttribute(["a", "an", "the"], "article"); // set all tokens contained in given dictionary to an attribute
  $tas->getAttributesAtToken(0); // "article"
```

## Installation
### Using Composer (recommended)
```
composer require apemsel/AttributedString
```

## Documentation
See the generated phpdoc API documentation in the doc/ directory or try
http://htmlpreview.github.io/?https://raw.githubusercontent.com/apemsel/AttributedString/master/doc/index.html
