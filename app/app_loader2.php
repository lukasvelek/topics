<?php

chdir('..\\');

require_once('app/app_loader.php');
require_once('config.local.php');

$objects = [];

function createObject(string $name) {
    global $cfg;
    $class = new ReflectionClass($name);

    $constructor = $class->getConstructor();

    if($constructor === null) {
        return null;
    }

    $params = $constructor->getParameters();

    $tmp = [];
    foreach($params as $param) {
        if($param->getName() == 'cfg') {
            $tmp[] = $cfg;
        } else {
            $type = $param->getType();

            if(in_array($type->getName(), ['int', 'bool', 'string', 'array'])) {
                $upper = strtoupper($param->getName());

                if(isset($cfg[$upper])) {
                    $tmp[] = $cfg[$upper];

                    continue;
                } else {
                    throw new Exception('Error.');
                }
            }

            $t = getObject($param->getType());

            $tmp[] = $t;
        }
    }

    try {
        $x = new $name(...$tmp);
    } catch(Exception $e) {
        throw $e;
    }

    return $x;
}

function getObject(string $name) {
    global $objects;

    $class = new ReflectionClass($name);
    if($class->isAbstract() || !$class->isInstantiable()) return null;

    if(array_key_exists($name, $objects)) {
        return $objects[$name];
    } else {
        $obj = createObject($name);
        if($obj === null) {
            return null;
        }
        $objects[$name] = $obj;
        return $obj;
    }
}

$contains = [
    'Repository', 'Logger', 'DatabaseConnection', 'CacheManager', 'Manager'
];

$classes = get_declared_classes();

foreach($classes as $class) {
    if(explode('\\', $class)[0] != 'App') {
        continue;
    }

    $class = explode('.', $class)[0];

    foreach($contains as $c) {
        if(str_contains($class, $c) && !str_contains($class, 'Exception')) {
            $x = getObject($class);

            if($x !== null) {
                $objects[$class] = $x;
            }
        }
    }
}

createContainer2();

function createContainer2() {
    global $objects, $cfg;

    $containerName = 'container2_' . date('Y-m-d') . '.php';
    if(file_exists('cache\\' . $containerName)) {
        require_once('cache\\' . $containerName);
        return;
    }

    $tmp = [];

    $s = function(string|array $text) use (&$tmp) {
        if(is_array($text)) {
            $text = implode('', $text);
        }

        $tmp[] = $text;
    };

    $s('<?php');

    $s('class Container2_' . date('Y_m_d') . ' extends AContainer {');

    $map = [];
    foreach($objects as $name => $object) {
        $map[] = $name;
    }

    $functions = [];
    $if = [];

    $w = 0;
    foreach($objects as $name => $object) {
        $functions['create_' . $w] = [
            'return new ' . $name . '('
        ];
        $if[$name] = 'create_' . $w;

        $ref = new ReflectionClass($object);

        $constParams = $ref->getConstructor()->getParameters();

        $p = [];
        foreach($constParams as $cp) {
            /*$q = null;
            array_walk($map, function($x, $i) use ($cp, &$q) {
                if($x == $cp->getType()) {
                    $q = $i;
                }
            });

            if($q !== null) {
                $p[] = '$this->create_' . $q . '()';
            } else {
                if($cp->getName() == 'cfg') {
                    $p[] = '$this->getCfg()';
                } else {
                    $up = strtoupper($cp->getName());
                    
                    if(isset($cfg[$up])) {
                        $cv = $cfg[$up];

                        if(is_bool($cv)) {
                            if($cv == true) {
                                $cv = 'true';
                            } else {
                                $cv = 'false';
                            }
                        }

                        $p[] = $cv;
                    }
                }
            }*/

            if(isset($if[$cp->getType()->getName()])) {
                $p[] = '$this->get(\'' . $cp->getType()->getName() . '\')';
            } else {
                $up = strtoupper($cp->getName());
                    
                if(isset($cfg[$up])) {
                    $cv = $cfg[$up];

                    if(is_bool($cv)) {
                        if($cv == true) {
                            $cv = 'true';
                        } else {
                            $cv = 'false';
                        }
                    }

                    $p[] = $cv;
                } else {
                    if($cp->getName() == 'cfg') {
                        $p[] = '$this->getCfg()';
                    }
                }
            }
        }

        $functions['create_' . $w][] = implode(', ', $p);
        $functions['create_' . $w][] = ');';

        $w++;
    }

    $cfg2 = [];
    foreach($cfg as $k => $v) {
        if(!is_integer($v) && !is_bool($v)) {
            $v = str_replace('\\', '\\\\', $v);
            $v = '\'' . $v . '\'';
        }

        if(is_bool($v)) {
            if($v == false) {
                $v = 'false';
            } else {
                $v = 'true';
            }
        }

        $cfg2[] = '\'' . $k . '\' => ' . $v;
    }

    $functions['getCfg'] = [
        'return [' . implode(', ', $cfg2) . '];'
    ];

    $ff = [];
    foreach($functions as $f => $x) {
        $ff[] = '\'' . $f . '\'';
    }

    $s('public function get(string $name) {');
    /*$s('if(array_key_exists($name, $this->functions)) {');
    $s('return $this->functions[$name];');
    $s('} else {');
    $s('$code = $this->getCodeByClassName($name);');
    $s('return \'create_\' . $code();');
    $s('}');*/
    $s('if(array_key_exists($name, $this->objects)) {');
    $s('return $this->objects[$name];');
    $s('} else {');
    $s('$code = $this->getCodeByClassName($name);');
    $s('$x = \'create_\' . $code;');
    $s('$o = $x();');
    $s('$this->objects[$name] = $o;');
    $s('return $o;');
    $s('}');
    $s('}');

    foreach($functions as $name => $content) {
        $s('public function ' . $name . '() {');
        $s(implode('', $content));
        $s('}');
    }

    $s('public function getCodeByClassName(string $cn) {');
    $s('return match($cn) {');
    
    $r = [];
    foreach($map as $i => $name) {
        $r[] = '\'' . $name . '\' => ' . $i;
    }

    $s(implode(',', $r));

    $s('};');
    $s('}');

    $s('}');

    $s('?>');

    file_put_contents('cache\\' . $containerName, implode("\r\n", $tmp));
    return;
}

function run() {
    require_once('cache\\container2_' . date('Y-m-d') . '.php');

    $className = 'Container2_' . date('Y_m_d');
    $c = new $className();

    $logger = $c->get('App\\Logger\\Logger');

    $logger->logInfo('pipi');
}

run();

?>