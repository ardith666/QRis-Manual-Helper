<?php
require_once __DIR__ . '/QrisException.php';
require_once __DIR__ . '/QrisValidator.php';
require_once __DIR__ . '/QrisService.php';

if (!class_exists('QrisException', false)) {
    class_alias('Ardith666\\QrisManualHelper\\QrisException', 'QrisException');
}
if (!class_exists('QrisValidator', false)) {
    class_alias('Ardith666\\QrisManualHelper\\QrisValidator', 'QrisValidator');
}
if (!class_exists('QrisService', false)) {
    class_alias('Ardith666\\QrisManualHelper\\QrisService', 'QrisService');
}