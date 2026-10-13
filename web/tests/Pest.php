<?php

/*
| Las pruebas de Feature usan la aplicación Laravel completa (Tests\TestCase).
*/

pest()->extend(Tests\TestCase::class)->in('Feature');
