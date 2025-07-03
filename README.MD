1. composer install
2. run ```php index.php```

Composer used for:
- autoload (PSR4 standard)
- using symfony vardumper

What does this project has:

Eloquent like implementation to work with json files:
- each "table" (store) is placed to separeted file (data/`name of model`.json). If there is no such file - it creates if
- available static methods (Eloquent style): ``select()``, ``where()``, ``create()``, ``find()``, ``first()``, ``get()``, ``count()`` and usage in chains like User::where(...)->where(...)->get();
- available non-static methods ``update()``, ``destroy()``
- replacing default primary key in model (protected $primaryKey)
- ability to add getters like getName()