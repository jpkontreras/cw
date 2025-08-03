# Transforming to TypeScript

### On this page

1. Installation of extra package
2. Usage

Thanks to the typescript-transformer package, it is possible to

automatically transform data objects into TypeScript definitions.

For example, the following data object:

```
classDataObjectextendsData{publicfunction__construct(publicnull|int$nullable,publicint$int,publicbool$bool,publicstring$string,publicfloat$float,/**@varstring[]*/publicarray$array,publicLazy|string$lazy,publicOptional|string$optional,publicSimpleData$simpleData,/**@var\Spatie\LaravelData\Tests\Fakes\SimpleData[]*/publicDataCollection$dataCollection,)
    {
    }
}
```

... can be transformed to the following TypeScript type:

```
{
    nullable: number | null;
    int: number;
    bool: boolean;
    string: string;
    float: number;
    array: Array<string>;
    lazy? : string;
    optional? : string;
    simpleData: SimpleData;
    dataCollection: Array<SimpleData>;
}
```

## # # Installation of extra package

First, you must install the spatie/laravel-typescript-transformer into your project.

```
composer require spatie/laravel-typescript-transformer
```

Next, publish the config file of the typescript-transformer package with:

```
php artisan vendor:publish --tag=typescript-transformer-config
```

Finally, add the Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer transformer to the

transformers in the typescript-transformer.php config file.

If you're using the DtoTransformer provided by the package, then be sure to put the DataTypeScriptTransformer before the DtoTransformer.

## # # Usage

Annotate each data object that you want to transform to Typescript with a /** @typescript */ annotation or

a #[TypeScript] attribute.

To generate the typescript file

, run this command:

```
php artisan typescript:transform
```

If you want to transform all the data objects within your application to TypeScript, you can use

the DataTypeScriptCollector, which should be added to the collectors in typescript-transformer.php.

If you're using the DefaultCollector provided by the package, then be sure to put the DataTypeScriptCollector before the DefaultCollector.

### # # Optional types

An optional or lazy property will automatically be transformed into an optional type within TypeScript:

```
classDataObjectextendsData{publicfunction__construct(publicLazy|string$lazy,publicOptional|string$optional,)
    {
    }
}
```

This will be transformed into:

```
{
    lazy? : string;
    optional? : string;
}
```

If you want to have optional typed properties in TypeScript without typing your properties optional or lazy within PHP,

then you can use the Optional attribute from the typescript-transformer package.

Don't forget to alias it as TypeScriptOptional when you're already using this package's Optional type!

```
useSpatie\TypeScriptTransformer\Attributes\OptionalasTypeScriptOptional;classDataObjectextendsData{publicfunction__construct(#[TypeScriptOptional]publicint$id,publicstring$someString,publicOptional|string$optional,)
    {
    }
}
```

Eloquent casting

Working with dates

Help us improve this page

### On this page

- Installation of extra package
- Usage

Writing Readable PHP

Learn everything about maintainable code in our online course

Learn more

Help us improve this page

- Products
- Open Source
- Courses
- Web Development

VacanciesAboutBlogDocsGuidelinesMerch ↗

Log in

Kruikstraat 22, Box 12

2018 Antwerp, Belgium

info@spatie.be

+32 3 292 56 79

- GitHub
- Instagram
- LinkedIn
- Twitter
- Bluesky
- Mastodon
- YouTube

- Privacy
- Disclaimer

+32 3 292 56 79

Our office is closed now, email us instead

ESC