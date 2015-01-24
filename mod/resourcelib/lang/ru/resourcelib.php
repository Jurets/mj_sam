<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * English strings for resourcelib
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_resourcelib
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'resourcelib';
$string['modulenameplural'] = 'Библиотека ресурсов';
$string['modulename_help'] = 'Система должна позволять создавать на общих веб-ссылок за пределами курса. Есть несколько полей метаданных, которые будут собраны. Это сделано за пределами курса, чтобы обеспечить повторное использование курсов. Ресурсы будут организованы в список подразделов, и несколько подразделов могут отображаться одновременно.
В ходе, пользователь может вставить один или несколько списков (с подразделами) на одной странице. Если количество списков больше единицы, списки (с подразделами) будет отображаться в виде вкладок (используя код фреймворка Bootstrap).
Каждый ресурс будет содержать значок, название и ссылку на видео, наряду с другими метаданными. Система будет записывать клики на каждой ссылке в журнале событий. Пользователи также будут иметь возможность оценить каждый ресурс, используя систему звездочный рейтинг. Они могут выбрать рейтинг один раз в ресурс в ходе, и не может его изменить. После того, как рейтинг был выбран, пользователь сможет увидеть средний рейтинг ресурса в этом процессе.';
$string['resourcelibfieldset'] = 'Элементы библиотеки';
$string['resourcelibname'] = 'resourcelib name';
$string['resourcelibname_help'] = 'This is the content of the help tooltip associated with the resourcelibname field. Markdown syntax is supported.';
$string['resourcelib'] = 'resourcelib';
$string['pluginadministration'] = 'resourcelib administration';
$string['pluginname'] = 'resourcelib';

$string['resourcelib:addinstance'] = 'Добавить Библиотеку Ресурсов';
$string['resourcelib:submit'] = 'Submit resourcelib';
$string['resourcelib:view'] = 'View resourcelib';

$string['resource'] = 'Ресурс';
$string['section'] = 'Секция';

$string['settings'] = 'Настройки Библиотеки Ресурсов';
$string['administration'] = 'Администрирование Библиотеки Ресурсов';
$string['manage_types'] = 'Управление Типами Ресурсов';
$string['manage_items'] = 'Управление Экземплярами Ресурсов';
$string['manage_lists'] = 'Управление Списками Ресурсов';
$string['manage_sections'] = 'Управление Секциями Ресурсов';

$string['addtype'] = 'Добавить Тип Ресурса';
$string['edittype'] = 'Изменить Тип Ресурса';
$string['deletetype'] = 'Удалить Тип Ресурса';

$string['addsection'] = 'Добавить Секцию';
$string['editsection'] = 'Изменить Секцию';
$string['deletesection'] = 'Удалить Секцию';
$string['viewsection'] = 'Просмотр Секции';
$string['add_section_resource'] = 'Добавить Ресурс в Секцию';
$string['del_section_resource'] = 'Удалить Ресурс из Секции';

$string['addlist'] = 'Добавить Список Ресурсов';
$string['editlist'] = 'Изменить Список Ресурсов';
$string['deletelist'] = 'Удалить Список Ресурсов';
$string['viewlist'] = 'Просмотр Списка Ресурсов';
$string['add_list_section'] = 'Добавить Секцию в Список';
$string['del_list_section'] = 'Удалить Секцию из Списка';

$string['additem'] = 'Добавить Ресурс';
$string['edititem'] = 'Изменить Ресурс';
$string['deleteitem'] = 'Удалить Ресурс';

$string['type'] = 'Тип';
$string['copyright'] = 'Копирайт';
$string['author'] = 'Автор';
$string['source'] = 'Источник';
$string['time_estimate'] = 'Оценка Времени';
$string['embed_code'] = 'Код для Вставки';
$string['display_name'] = 'Отображаемое Имя';
$string['section_count'] = 'Кол-во секций';
$string['resource_count'] = 'Кол-во ресурсов';

$string['missing_resource'] = 'Пропущен ресурс';
$string['missing_section'] = 'Пропущена Секция';
$string['no_resources'] = 'Нет Ресурсов в данной Секции';
$string['no_sections'] = 'Нет Секций в данном Списке';

$string['deletecheck_resurce_fromsection'] = 'Вы действительно хотите удалить ресурс {$a} из секции?';
$string['deletecheck_section_fromlist'] = 'Вы действительно хотите удалить секцию {$a} из списка?';
$string['enter_estimated_time'] = 'Введите расчетное время, чтобы прочитать этот ресурс (в минутах)';
$string['resources_exists'] = 'Существуют ресурсы данного типа';
$string['section_resource_exists'] = 'Существуют ресурсы в данном Секции';
$string['section_exists'] = 'Существуют секции в данном списке';
$string['resources_exists_in_section'] = 'Этот ресурс присутствует в секциях';

$string['listfield'] = 'Список Ресурсов';
$string['listfield_help'] = 'Выберите список. Вы можете выбирать несколько элементов с помощью одновременного нажатия клавиши <Ctrl> и мыши';