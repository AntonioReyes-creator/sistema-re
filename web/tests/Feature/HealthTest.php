<?php

it('responde en el endpoint de salud', function () {
    $this->get('/up')->assertOk();
});

it('muestra la página de inicio', function () {
    $this->get('/')->assertOk();
});
