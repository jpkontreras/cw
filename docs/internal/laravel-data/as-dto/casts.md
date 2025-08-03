# Casts

### On this page

1. Local casts
2. Global casts
3. Creating your own casts
4. Casting arrays or collections of non-data types

We extend our example data object just a little bit:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,publicDateTime$date,publicFormat$format,) {
    }
}
```

The Format property here is an Enum and looks like this:

```
enumFormat:string{casecd='cd';casevinyl='vinyl';casecassette='cassette';
}
```

When we now try to construct a data object like this:

```
SongData::from(['title'=>'Never gonna give you up','artist'=>'Rick Astley','date'=>'27-07-1987','format'=>'vinyl',
]);
```

And get an error because the first two properties are simple PHP types(strings, ints, floats, booleans, arrays), but the following two properties are more complex types: DateTime and Enum, respectively.

These types cannot be automatically created. A cast is needed to construct them from a string.

There are two types of casts, local and global casts.

## # # Local casts

Local casts are defined within the data object itself and can be added using attributes:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,#[WithCast(DateTimeInterfaceCast::class)]publicDateTime$date,#[WithCast(EnumCast::class)]publicFormat$format,) {
    }
}
```

Now it is possible to create a data object like this without exceptions:

```
SongData::from(['title'=>'Never gonna give you up','artist'=>'Rick Astley','date'=>'27-07-1987','format'=>'vinyl',
]);
```

It is possible to provide parameters to the casts like this:

```
#[WithCast(EnumCast::class,type:Format::class)]publicFormat$format
```

## # # Global casts

Global casts are not defined on the data object but in your data.php config file:

```
'casts'=> [DateTimeInterface::class=>Spatie\LaravelData\Casts\DateTimeInterfaceCast::class,
],
```

When the data object can find no local cast for the property, the package will look through the global casts and tries to find a suitable cast. You can define casts for:

- a specific implementation (e.g. CarbonImmutable)
- an interface (e.g. DateTimeInterface)
- a base class (e.g. Enum)

As you can see, the package by default already provides a DateTimeInterface cast, this means we can update our data object like this:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,publicDateTime$date,#[WithCast(EnumCast::class)]publicFormat$format,) {
    }
}
```

Tip: we can also remove the EnumCast since the package will automatically cast enums because they're a native PHP type, but this made the example easy to understand.

## # # Creating your own casts

It is possible to create your casts. You can read more about this in the advanced chapter.

## # # Casting arrays or collections of non-data types

We've already seen how collections of data can be made of data objects, the same is true for all other types if correctly

typed.

Let say we have an array of DateTime objects:

```
classReleaseDataextendsData{publicstring$title;/**@vararray<int, DateTime>*/publicarray$releaseDates;
}
```

By enabling the cast\_and\_transform\_iterables feature in the data config file (this feature will be enabled by default in laravel-data v5):

```
'features'=> ['cast_and_transform_iterables'=>true,
],
```

We now can create a ReleaseData object with an array of strings which will be cast into an array DateTime objects:

```
ReleaseData::from(['title'=>'Never Gonna Give You Up','releaseDates'=> ['1987-07-27T12:00:00Z','1987-07-28T12:00:00Z','1987-07-29T12:00:00Z',
    ],
]);
```

For this feature to work, a cast should not only implement the Cast interface but also the IterableItemCast. The

signatures of the cast and castIterableItem methods are exactly the same, but they're called on different times.

When casting a property like a DateTime from a string, the cast method will be used, when transforming an iterable

property like an array or Laravel Collection where the iterable item is typed using an annotation, then each item of the

provided iterable will trigger a call to the castIterableItem method.

Abstract Data

Optional properties

Help us improve this page

### On this page

- Local casts
- Global casts
- Creating your own casts
- Casting arrays or collections of non-data types

Laravel beyond CRUD

Check out our course on Laravel development for large apps

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