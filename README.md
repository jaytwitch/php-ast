This extension exposes the abstract syntax tree generated by PHP 7.

This extension is experimental and the representation of the syntax tree is not final.

API overview
------------

Defines:

 * `ast\Node` class
 * `ast\Node\Decl` class
 * `ast\AST_*` kind constants (mirroring `zend_ast.h`)
 * `ast\flags\*` flags
 * `ast\parse_file(string $filename [, int $version])`
 * `ast\parse_code(string $code [, int $version [, string $filename = "string code"]])`
 * `ast\get_kind_name(int $kind)`
 * `ast\kind_uses_flags(int $kind)`

Usage
-----

The `ast\parse_code()` function accepts a source code string or the `ast\parse_file()` which accepts
a filename containing PHP code (which is parsed in INITIAL mode, i.e.  it should generally include an
opening PHP tag) and returns an abstract syntax tree consisting of `ast\Node` objects. `ast\Node` is
declared as follows:

```php
namespace ast;
class Node {
    public $kind;
    public $flags;
    public $lineno;
    public $children;
}
```

The `kind` property specified the type of the node. It is an integral value, which corresponds to
one of the `ast\AST_*` constants, for example `ast\AST_STMT_LIST`. You can retrieve the string name
of an integral kind by passing it to `ast\get_kind_name()`.

The `flags` property contains node specific flags. It is always defined, but for most nodes it is
always zero. `ast\kind_uses_flags()` can be used to determine whether a certain kind has a
meaningful flags value. Which nodes use which flags is explained in the "Flags" section below.

The `lineno` property specifies the *starting* line number of the node.

The `children` property contains an array of child-nodes. These children can be either other
`ast\Node` objects or plain values. The meaning of the children is node-specific and should be
deduced from context or by looking at the [parser definition][parser].

Function and class declarations use `ast\Node\Decl` objects instead, which specify a number of
additional properties:

```php
namespace ast\Node;
use ast\Node;

class Decl extends Node {
    public $endLineno;
    public $name;
    public $docComment;
}
```

`endLineno` provides the end line number of the declaration, `name` contains the name of the
function or class (can be `null` for anonymous classes) and `docComment` contains the preceding
doc comment or `null` if no doc comment was used.

Simple usage example:

```php
<?php

$code = <<<'EOC'
<?php
$var = 42;
EOC;

var_dump(ast\parse_code($code));

// Output:
object(ast\Node)#1 (4) {
  ["kind"]=>
  int(133)
  ["flags"]=>
  int(0)
  ["lineno"]=>
  int(1)
  ["children"]=>
  array(1) {
    [0]=>
    object(ast\Node)#2 (4) {
      ["kind"]=>
      int(517)
      ["flags"]=>
      int(0)
      ["lineno"]=>
      int(1)
      ["children"]=>
      array(2) {
        [0]=>
        object(ast\Node)#3 (4) {
          ["kind"]=>
          int(256)
          ["flags"]=>
          int(0)
          ["lineno"]=>
          int(1)
          ["children"]=>
          array(1) {
            [0]=>
            string(3) "var"
          }
        }
        [1]=>
        int(42)
      }
    }
  }
}
```

The [`util.php`][util] file defines an `ast_dump()` function, which can be used to create a more
compact and human-readable dump of the AST structure:

```php
<?php

require 'path/to/util.php';

$code = <<<'EOC'
<?php
$var = 42;
EOC;

echo ast_dump(ast\parse_code($code)), "\n";

// Output:
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "var"
        1: 42
```

To additionally show line numbers pass the `AST_DUMP_LINENOS` option as the second argument to
`ast_dump()`.

A more substantial AST dump can be found [in the tests][test_dump].

Flags
-----

This section lists which flags are used by which AST node kinds. The "combinable" flags can be
combined using bitwise or and should be checked by using `$ast->flags & ast\flags\FOO`. The
"exclusive" flags are used standalone and should be checked using `$ast->flags === ast\flags\BAR`.

```
// Used by ast\AST_ARRAY_ELEM and ast\AST_CLOSURE_VAR (exclusive)
1 = by-reference

// Used by ast\AST_NAME (exclusive)
ast\flags\NAME_FQ (= 0)
ast\flags\NAME_NOT_FQ
ast\flags\NAME_RELATIVE

// Used by ast\AST_METHOD, ast\AST_PROP_DECL, ast\AST_TRAIT_ALIAS (combinable)
ast\flags\MODIFIER_PUBLIC
ast\flags\MOFIFIER_PROTECTED
ast\flags\MOFIFIER_PRIVATE
ast\flags\MOFIFIER_STATIC
ast\flags\MOFIFIER_ABSTRACT
ast\flags\MOFIFIER_FINAL

// Used by ast\AST_CLOSURE (combinable)
ast\flags\MODIFIER_STATIC

// Used by ast\AST_FUNC_DECL, ast\AST_METHOD, ast\AST_CLOSURE (combinable)
ast\flags\RETURNS_REF

// Used by ast\AST_CLASS (exclusive)
ast\flags\CLASS_ABSTRACT
ast\flags\CLASS_FINAL
ast\flags\CLASS_TRAIT
ast\flags\CLASS_INTERFACE

// Used by ast\AST_PARAM (exclusive)
ast\flags\PARAM_REF
ast\flags\PARAM_VARIADIC

// Used by ast\AST_TYPE (exclusive)
ast\flags\TYPE_ARRAY
ast\flags\TYPE_CALLABLE

// Used by ast\AST_CAST (exclusive)
ast\flags\TYPE_NULL
ast\flags\TYPE_BOOL
ast\flags\TYPE_LONG
ast\flags\TYPE_DOUBLE
ast\flags\TYPE_STRING
ast\flags\TYPE_ARRAY
ast\flags\TYPE_OBJECT

// Used by ast\AST_UNARY_OP (exclusive)
ast\flags\UNARY_BOOL_NOT
ast\flags\UNARY_BITWISE_NOT
ast\flags\UNARY_MINUS   // since version 20
ast\flags\UNARY_PLUS    // since version 20
ast\flags\UNARY_SILENCE // since version 20

// Used by ast\AST_BINARY_OP and ast\AST_ASSIGN_OP in version >= 20 (exclusive)
ast\flags\BINARY_BITWISE_OR
ast\flags\BINARY_BITWISE_AND
ast\flags\BINARY_BITWISE_XOR
ast\flags\BINARY_CONCAT
ast\flags\BINARY_ADD
ast\flags\BINARY_SUB
ast\flags\BINARY_MUL
ast\flags\BINARY_DIV
ast\flags\BINARY_MOD
ast\flags\BINARY_POW
ast\flags\BINARY_SHIFT_LEFT
ast\flags\BINARY_SHIFT_RIGHT

// Used by ast\AST_BINARY_OP (exclusive)
ast\flags\BINARY_BOOL_AND            // since version 20
ast\flags\BINARY_BOOL_OR             // since version 20
ast\flags\BINARY_BOOL_XOR
ast\flags\BINARY_IS_IDENTICAL
ast\flags\BINARY_IS_NOT_IDENTICAL
ast\flags\BINARY_IS_EQUAL
ast\flags\BINARY_IS_NOT_EQUAL
ast\flags\BINARY_IS_SMALLER
ast\flags\BINARY_IS_SMALLER_OR_EQUAL
ast\flags\BINARY_IS_GREATER          // since version 20
ast\flags\BINARY_IS_GREATER_OR_EQUAL // since version 20
ast\flags\BINARY_SPACESHIP

// Used by ast\AST_ASSIGN_OP in version 10 (exclusive)
ast\flags\ASSIGN_BITWISE_OR
ast\flags\ASSIGN_BITWISE_AND
ast\flags\ASSIGN_BITWISE_XOR
ast\flags\ASSIGN_CONCAT
ast\flags\ASSIGN_ADD
ast\flags\ASSIGN_SUB
ast\flags\ASSIGN_MUL
ast\flags\ASSIGN_DIV
ast\flags\ASSIGN_MOD
ast\flags\ASSIGN_POW
ast\flags\ASSIGN_SHIFT_LEFT
ast\flags\ASSIGN_SHIFT_RIGHT

// Used by ast\AST_MAGIC_CONST (exclusive)
// (Constants defined by ext\tokenizer)
T_LINE
T_FILE
T_DIR
T_TRAIT_C
T_METHOD_C
T_FUNC_C
T_NS_C
T_CLASS_C

// Used by ast\AST_USE, ast\AST_GROUP_USE and ast\AST_USE_ELEM (exclusive)
// (Constants defined by ext\tokenizer)
T_CLASS
T_FUNCTION
T_CONST

// Used by ast\AST_INCLUDE_OR_EVAL (exclusive)
ast\flags\EXEC_EVAL
ast\flags\EXEC_INCLUDE
ast\flags\EXEC_INCLUDE_ONCE
ast\flags\EXEC_REQUIRE
ast\flags\EXEC_REQUIRE_ONCE
```

Version changelog
-----------------

### 20 (unstable)

* `AST_GREATER`, `AST_GREATER_EQUAL`, `AST_OR`, `AST_AND` nodes are now represented using
  `AST_BINARY_OP` with flags `BINARY_IS_GREATER`, `BINARY_IS_GREATER_OR_EQUAL`, `BINARY_BOOL_OR`
  and `BINARY_BOOL_AND`.
* `AST_SILENCE`, `AST_UNARY_MINUS` and `AST_UNARY_PLUS` nodes are noew represented using
  `AST_UNARY_OP` with flags `UNARY_SILENCE`, `UNARY_MINUS` and `UNARY_PLUS`
* `AST_ASSIGN_OP` now uses `BINARY_*` flags instead of separate `ASSIGN_*` flags.

### 10 (current)

Initial.

  [parser]: http://lxr.php.net/xref/PHP_TRUNK/Zend/zend_language_parser.y
  [util]: https://github.com/nikic/php-ast/blob/master/util.php
  [test_dump]: https://github.com/nikic/php-ast/blob/master/tests/001.phpt
