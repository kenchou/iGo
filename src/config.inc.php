<?php
$path[] = get_include_path();
$path[] = './class';
$path[] = '../library';

set_include_path(implode(PATH_SEPARATOR, $path));
