<?php

it('tiene configurada la conexión con el procesador', function () {
    expect(config('services.procesador.url'))->toBeString()->not->toBeEmpty();
});
