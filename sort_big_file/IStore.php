<?php

interface IStore
{
    public function save(IObjectSave $object);

    public function load($id);
}
