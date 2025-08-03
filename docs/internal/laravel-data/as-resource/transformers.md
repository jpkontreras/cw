# Transforming data

### On this page

1. Local transformers
2. Global transformers
3. Getting a data object without transforming
4. Getting a data object (on steroids)
5. Transformation depth

Transformers allow you to transform complex types to simple types. This is useful when you want to transform a data object to an array or JSON.

No complex transformations are required for the default types (string, bool, int, float, enum and array), but special types like Carbon or a Laravel Model will need extra attention.

Transformers are simple classes that will convert a such complex types to something simple like a string or int. For example, we can transform a Carbon object to 16-05-1994, 16-05-1994T00:00:00+00 or something completely different.

There are two ways you can define transformers: locally and globally.

## # # Local transformers

When you want to transform a specific property, you can use an attribute with the transformer you want to use:

```
classArtistDataextendsData{publicfunction__construct(publicstring$name,#[WithTransformer(DateTimeInterfaceTransformer::class)]publicCarbon$birth_date) {
    }
}
```

The DateTimeInterfaceTransformer is shipped with the package and will transform objects of type Carbon, CarbonImmutable, DateTime and DateTimeImmutable to a string.

The format used for converting the date to string can be set in the data.php config file. It is also possible to manually define a format:

```
classArtistDataextendsData{publicfunction__construct(publicstring$name,#[WithTransformer(DateTimeInterfaceTransformer::class,format:'m-Y')]publicCarbon$birth_date) {
    }
}
```

Next to a DateTimeInterfaceTransformer the package also ships with an ArrayableTransformer that transforms an Arrayable object to an array.

It is possible to create transformers for your specific types. You can find more info here.

## # # Global transformers

Global transformers are defined in the data.php config file and are used when no local transformer for a property was added. By default, there are two transformers:

```
useIlluminate\Contracts\Support\Arrayable;useSpatie\LaravelData\Transformers\ArrayableTransformer;useSpatie\LaravelData\Transformers\DateTimeInterfaceTransformer;/*
 * Global transformers will take complex types and transform them into simple
 * types.
 */'transformers'=> [DateTimeInterface::class=>DateTimeInterfaceTransformer::class,Arrayable::class=>ArrayableTransformer::class,
],
```

The package will look through these global transformers and tries to find a suitable transformer. You can define transformers for:

- a specific implementation (e.g. CarbonImmutable)
- an interface (e.g. DateTimeInterface)
- a base class (e.g. Enum)

## # # Getting a data object without transforming

It is possible to get an array representation of a data object without transforming the properties. This means Carbon objects won't be transformed into strings. And also, nested data objects and DataCollections won't be transformed into arrays. You can do this by calling the all method on a data object like this:

```
ArtistData::from($artist)->all();
```

## # # Getting a data object (on steroids)

Internally the package uses the transform method for operations like toArray, all, toJson and so on. This method is highly configurable, when calling it without any arguments it will behave like the toArray method:

```
ArtistData::from($artist)->transform();
```

Producing the following result:

```
['name'=>'Rick Astley','birth_date'=>'06-02-1966',
]
```

It is possible to disable the transformation of values, which will make the transform method behave like the all method:

```
useSpatie\LaravelData\Support\Transformation\TransformationContext;ArtistData::from($artist)->transform(TransformationContextFactory::create()->withoutValueTransformation()
);
```

Outputting the following array:

```
['name'=>'Rick Astley','birth_date'=>Carbon::parse('06-02-1966'),
]
```

The mapping of property names can also be disabled:

```
ArtistData::from($artist)->transform(TransformationContextFactory::create()->withoutPropertyNameMapping()
);
```

It is possible to enable wrapping the data object:

```
useSpatie\LaravelData\Support\Wrapping\WrapExecutionType;ArtistData::from($artist)->transform(TransformationContextFactory::create()->withWrapping()
);
```

Outputting the following array:

```
['data'=> ['name'=>'Rick Astley','birth_date'=>'06-02-1966',
    ],
]
```

You can also add additional global transformers as such:

```
ArtistData::from($artist)->transform(TransformationContextFactory::create()->withGlobalTransformer('string',StringToUpperTransformer::class)
);
```

## # # Transformation depth

When transforming a complicated structure of nested data objects it is possible that an infinite loop is created of data objects including each other.

To prevent this, a transformation depth can be set, when that depth is reached when transforming, either an exception will be thrown or an empty

array is returned, stopping the transformation.

This transformation depth can be set globally in the data.php config file:

```
'max_transformation_depth'=> 20,
```

Setting the transformation depth to null will disable the transformation depth check:

```
'max_transformation_depth'=>null,
```

It is also possible if a MaxTransformationDepthReached exception should be thrown or an empty array should be returned:

```
'throw_when_max_transformation_depth_reached'=>true,
```

It is also possible to set the transformation depth on a specific transformation by using a TransformationContextFactory:

```
ArtistData::from($artist)->transform(TransformationContextFactory::create()->maxDepth(20)
);
```

By default, an exception will be thrown when the maximum transformation depth is reached. This can be changed to return an empty array as such:

```
ArtistData::from($artist)->transform(TransformationContextFactory::create()->maxDepth(20,throw:false)
);
```

Including and excluding properties

Eloquent casting

Help us improve this page

### On this page

- Local transformers
- Global transformers
- Getting a data object without transforming
- Getting a data object (on steroids)
- Transformation depth

Medialibrary.pro

UI components for the Media Library

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