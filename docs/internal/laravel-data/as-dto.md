# Abstract Data

It is possible to create an abstract data class with subclasses extending it:

```
abstractclassPersonextendsData{publicstring$name;
}classSingerextendsPerson{publicfunction__construct(publicstring$voice,) {}
}classMusicianextendsPerson{publicfunction__construct(publicstring$instrument,) {}
}
```

It is perfectly possible now to create individual instances as follows:

```
Singer::from(['name'=>'Rick Astley','voice'=>'tenor']);Musician::from(['name'=>'Rick Astley','instrument'=>'guitar']);
```

But what if you want to use this abstract type in another data class like this:

```
classContractextendsData{publicstring$label;publicPerson$artist;
}
```

While the following may both be valid:

```
Contract::from(['label'=>'PIAS','artist'=> ['name'=>'Rick Astley','voice'=>'tenor']]);Contract::from(['label'=>'PIAS','artist'=> ['name'=>'Rick Astley','instrument'=>'guitar']]);
```

The package can't decide which subclass to construct for the property.

You can implement the PropertyMorphableData interface on the abstract class to solve this. This interface adds a morph method that will be used to determine which subclass to use. The morph method receives an array of properties limited to properties tagged by a PropertyForMorph attribute.

```
useSpatie\LaravelData\Attributes\PropertyForMorph;useSpatie\LaravelData\Contracts\PropertyMorphableData;abstractclassPersonextendsDataimplementsPropertyMorphableData{#[PropertyForMorph]publicstring$type;publicstring$name;publicstaticfunctionmorph(array$properties):?string{returnmatch($properties['type']) {'singer'=>Singer::class,'musician'=>Musician::class,default=>null};
    }
}
```

The example above will work by adding this code, and the correct Data class will be constructed.

Since the morph functionality needs to run early within the data construction process, it bypasses the normal flow of constructing data objects so there are a few limitations:

- it is only allowed to use properties typed as string, int, or BackedEnum(int or string)
- When a property is typed as an enum, the value passed to the morph method will be an enum
- it can be that the value of a property within the morph method is null or a different type than expected since it runs before validation
- properties with mapped property names are still supported

It is also possible to use abstract data classes as collections as such:

```
classBandextendsData{publicstring$name;/**@vararray<Person>*/publicarray$members;
}
```

Collections

Casts






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

By enabling the cast_and_transform_iterables feature in the data config file (this feature will be enabled by default in laravel-data v5):

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






# Collections

### On this page

1. Magically creating collections
2. Creating a data object with collections
3. DataCollections, PaginatedDataCollections and CursorPaginatedCollections

It is possible to create a collection of data objects by using the collect method:

```
SongData::collect([
    ['title'=>'Never Gonna Give You Up','artist'=>'Rick Astley'],
    ['title'=>'Giving Up on Love','artist'=>'Rick Astley'],
]);// returns an array of SongData objects
```

Whatever type of collection you pass in, the package will return the same type of collection with the freshly created

data objects within it. As long as this type is an array, Laravel collection or paginator or a class extending from it.

This opens up possibilities to create collections of Eloquent models:

```
SongData::collect(Song::all());// return an Eloquent collection of SongData objects
```

Or use a paginator:

```
SongData::collect(Song::paginate());// return a LengthAwarePaginator of SongData objects// orSongData::collect(Song::cursorPaginate());// return a CursorPaginator of SongData objects
```

Internally the from method of the data class will be used to create a new data object for each item in the collection.

When the collection already contains data objects, the collect method will return the same collection:

```
SongData::collect([SongData::from(['title'=>'Never Gonna Give You Up','artist'=>'Rick Astley']),SongData::from(['title'=>'Giving Up on Love','artist'=>'Rick Astley']),
]);// returns an array of SongData objects
```

The collect method also allows you to cast collections from one type into another. For example, you can pass in

an arrayand get back a Laravel collection:

```
SongData::collect($songs,Collection::class);// returns a Laravel collection of SongData objects
```

This transformation will only work with non-paginator collections.

## # # Magically creating collections

We've already seen that from can create data objects magically. It is also possible to create a collection of data

objects magically when using collect.

Let's say you've implemented a custom collection class called SongCollection:

```
classSongCollectionextendsCollection{publicfunction__construct($items = [],publicarray$artists= [],) {parent::__construct($items);
    }
}
```

Since the constructor of this collection requires an extra property it cannot be created automatically. However, it is

possible to define a custom collect method which can create it:

```
classSongDataextendsData{publicstring$title;publicstring$artist;publicstaticfunctioncollectArray(array$items):SongCollection{returnnewSongCollection(parent::collect($items),array_unique(array_map(fn(SongData$song) =>$song->artist,$items))
        );
    }
}
```

Now when collecting an array data objects a SongCollection will be returned:

```
SongData::collectArray([
    ['title'=>'Never Gonna Give You Up','artist'=>'Rick Astley'],
    ['title'=>'Living on a prayer','artist'=>'Bon Jovi'],
]);// returns an SongCollection of SongData objects
```

There are a few requirements for this to work:

- The method must be static
- The method must be public
- The method must have a return type
- The method name must start with collect
- The method name must not be collect

## # # Creating a data object with collections

You can create a data object with a collection of data objects just like you would create a data object with a nested

data object:

```
useApp\Data\SongData;useIlluminate\Support\Collection;classAlbumDataextendsData{publicstring$title;/**@varCollection<int, SongData>*/publicCollection$songs;
}AlbumData::from(['title'=>'Never Gonna Give You Up','songs'=> [
        ['title'=>'Never Gonna Give You Up','artist'=>'Rick Astley'],
        ['title'=>'Giving Up on Love','artist'=>'Rick Astley'],
    ]
]);
```

Since the collection type here is a Collection, the package will automatically convert the array into a collection of

data objects.

## # # DataCollections, PaginatedDataCollections and CursorPaginatedCollections

The package also provides a few collection classes which can be used to create collections of data objects. It was a

requirement to use these classes in the past versions of the package when nesting data objects collections in data

objects. This is no longer the case, but there are still valid use cases for them.

You can create a DataCollection like this:

```
useSpatie\LaravelData\DataCollection;SongData::collect(Song::all(),DataCollection::class);
```

A PaginatedDataCollection can be created like this:

```
useSpatie\LaravelData\PaginatedDataCollection;SongData::collect(Song::paginate(),PaginatedDataCollection::class);
```

And a CursorPaginatedCollection can be created like this:

```
useSpatie\LaravelData\CursorPaginatedCollection;SongData::collect(Song::cursorPaginate(),CursorPaginatedCollection::class);
```

### # # Why using these collection classes?

We advise you to always use arrays, Laravel collections and paginators within your data objects. But let's say you have

a controller like this:

```
classSongController{publicfunctionindex()
    {returnSongData::collect(Song::all());
    }
}
```

In the next chapters of this documentation, we'll see that it is possible to include or exclude properties from the data

objects like this:

```
classSongController{publicfunctionindex()
    {returnSongData::collect(Song::all(),DataCollection::class)->include('artist');
    }
}
```

This will only work when you're using a DataCollection, PaginatedDataCollection or CursorPaginatedCollection.

### # # DataCollections

DataCollections provide some extra functionalities like:

```
// Counting the amount of items in the collectioncount($collection);// Changing an item in the collection$collection[0]->title='Giving Up on Love';// Adding an item to the collection$collection[] =SongData::from(['title'=>'Never Knew Love','artist'=>'Rick Astley']);// Removing an item from the collectionunset($collection[0]);
```

It is even possible to loop over it with a foreach:

```
foreach($songsas$song){echo$song->title;
}
```

The DataCollection class implements a few of the Laravel collection methods:

- through
- map
- filter
- first
- each
- values
- where
- reduce
- sole

You can, for example, get the first item within a collection like this:

```
SongData::collect(Song::all(),DataCollection::class)->first();// SongData object
```

### # # The collection method

In previous versions of the package it was possible to use the collection method to create a collection of data

objects:

```
SongData::collection(Song::all());// returns a DataCollection of SongData objectsSongData::collection(Song::paginate());// returns a PaginatedDataCollection of SongData objectsSongData::collection(Song::cursorPaginate());// returns a CursorPaginatedCollection of SongData objects
```

This method was removed with version v4 of the package in favor for the more powerful collect method. The collection

method can still be used by using the WithDeprecatedCollectionMethod trait:

```
useSpatie\LaravelData\Concerns\WithDeprecatedCollectionMethod;classSongDataextendsData{useWithDeprecatedCollectionMethod;// ...}
```

Please note that this trait will be removed in the next major version of the package.

Nesting

Abstract Data






# Computed values

Earlier we saw how default values can be set for a data object, sometimes you want to set a default value based on other properties. For example, you might want to set a full_name property based on a first_name and last_name property. You can do this by using a computed property:

```
useSpatie\LaravelData\Attributes\Computed;classSongDataextendsData{#[Computed]publicstring$full_name;publicfunction__construct(publicstring$first_name,publicstring$last_name,) {$this->full_name="{$this->first_name} {$this->last_name}";
    }
}
```

You can now do the following:

```
SongData::from(['first_name'=>'Ruben','last_name'=>'Van Assche']);
```

Please notice: the computed property won't be reevaluated when its dependencies change. If you want to update a computed property, you'll have to create a new object.

Again there are a few conditions for this approach:

- You must always use a sole property, a property within the constructor definition won't work
- Computed properties cannot be defined in the payload, a CannotSetComputedValue will be thrown if this is the case
- If the ignore_exception_when_trying_to_set_computed_property_value configuration option is set to true, the computed property will be silently ignored when trying to set it in the payload and no CannotSetComputedValue exception will be thrown.






# Creating a data object

### On this page

1. Magical creation
2. Optional creation
3. Creation without magic methods
4. Advanced creation using factories
5. DTO classes

Let's get started with the following simple data object:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

Since this is just a simple PHP object, it can be initialized as such:

```
newSongData('Never gonna give you up','Rick Astley');
```

But with this package, you can initialize the data object also with an array:

```
SongData::from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

You can use the from method to create a data object from nearly anything. For example, let's say you have an Eloquent

model like this:

```
classSongextendsModel{// Your model code}
```

You can create a data object from such a model like this:

```
SongData::from(Song::firstOrFail($id));
```

The package will find the required properties within the model and use them to construct the data object.

Data can also be created from JSON strings:

```
SongData::from('{"title" : "Never Gonna Give You Up","artist" : "Rick Astley"}');
```

Although the PHP 8.0 constructor properties look great in data objects, it is perfectly valid to use regular properties

without a constructor like so:

```
classSongDataextendsData{publicstring$title;publicstring$artist;
}
```

## # # Magical creation

It is possible to overwrite or extend the behaviour of the from method for specific types. So you can construct a data

object in a specific manner for that type. This can be done by adding a static method starting with 'from' to the data

object.

For example, we want to change how we create a data object from a model. We can add a fromModel static method that

takes the model we want to use as a parameter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromModel(Song$song):self{returnnewself("{$song->title} ({$song->year})",$song->artist);
    }
}
```

Now when creating a data object from a model like this:

```
SongData::from(Song::firstOrFail($id));
```

Instead of the default method, the fromModel method will be called to create a data object from the found model.

You're truly free to add as many from methods as you want. For example, you could add one to create a data object from a

string:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromString(string$string):self{
        [$title,$artist] =explode('|',$string);returnnewself($title,$artist);
    }
}
```

From now on, you can create a data object like this:

```
SongData::from('Never gonna give you up|Rick Astley');
```

It is also possible to use multiple arguments in a magical creation method:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromMultiple(string$title,string$artist):self{returnnewself($title,$artist);
    }
}
```

Now we can create the data object like this:

```
SongData::from('Never gonna give you up','Rick Astley');
```

There are a few requirements to enable magical data object creation:

- The method must be static and public
- The method must start with from
- The method cannot be called from

When the package cannot find such a method for a type given to the data object's from method. Then the data object

will try to create itself from the following types:

- An Eloquent model by calling toArray on it
- A Laravel request by calling all on it
- An Arrayable by calling toArray on it
- An array

This list can be extended using extra normalizers, find more about

it here.

When a data object cannot be created using magical methods or the default methods, a CannotCreateData

exception will be thrown.

## # # Optional creation

It is impossible to return null from a data object's from method since we always expect a data object when

calling from. To solve this, you can call the optional method:

```
SongData::optional(null);// returns null
```

Underneath the optional method will call the from method when a value is given, so you can still magically create data

objects. When a null value is given, it will return null.

## # # Creation without magic methods

You can ignore the magical creation methods when creating a data object as such:

```
SongData::factory()->withoutMagicalCreation()->from($song);
```

## # # Advanced creation using factories

It is possible to configure how a data object is created, whether it will be validated, which casts to use and more. You

can read more about it here.

## # # DTO classes

The default Data class from which you extend your data objects is a multi versatile class, it packs a lot of

functionality. But sometimes you just want a simple DTO class. You can use the Dto class for this:

```
classSongDataextendsDto{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

The Dto class is a data class in its most basic form. It can be created from anything using magical methods, can

validate payloads before creating the data object and can be created using factories. But it doesn't have any of the

other functionality that the Data class has.

Quickstart

Nesting






# Default values

There are a few ways to define default values for a data object. Since a data object is just a regular PHP class, you can use the constructor to set default values:

```
classSongDataextendsData{publicfunction__construct(publicstring$title= 'Never Gonna Give You Up',publicstring$artist= 'Rick Astley',) {
    }
}
```

This works for simple types like strings, integers, floats, booleans, enums and arrays. But what if you want to set a default value for a more complex type like a CarbonImmutable object? You can use the constructor to do this:

```
classSongDataextendsData{#[Date]publicCarbonImmutable|Optional$date;publicfunction__construct(publicstring$title= 'Never Gonna Give You Up',publicstring$artist= 'Rick Astley',) {$this->date=CarbonImmutable::create(1987, 7, 27);
    }
}
```

You can now do the following:

```
SongData::from();SongData::from(['title'=>'Giving Up On Love','date'=>CarbonImmutable::create(1988, 4, 15)]);
```

Even validation will work:

```
SongData::validateAndCreate();SongData::validateAndCreate(['title'=>'Giving Up On Love','date'=>CarbonImmutable::create(1988, 4, 15)]);
```

There are a few conditions for this approach:

- You must always use a sole property, a property within the constructor definition won't work
- The optional type is technically not required, but it's a good idea to use it otherwise the validation won't work
- Validation won't be performed on the default value, so make sure it is valid

Mapping property names

Computed values






# Factories

### On this page

1. Disable property name mapping
2. Changing the validation strategy
3. Disabling magic methods
4. Disabling optional values
5. Adding additional global casts
6. Using the creation context

It is possible to automatically create data objects in all sorts of forms with this package. Sometimes a little bit more

control is required when a data object is being created. This is where factories come in.

Factories allow you to create data objects like before but allow you to customize the creation process.

For example, we can create a data object using a factory like this:

```
SongData::factory()->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

Collecting a bunch of data objects using a factory can be done as such:

```
SongData::factory()->collect(Song::all())
```

## # # Disable property name mapping

We saw earlier that it is possible to map

property names when creating a data object from an array. This can be disabled when using a factory:

```
ContractData::factory()->withoutPropertyNameMapping()->from(['name'=>'Rick Astley','record_company'=>'RCA Records']);// record_company will not be mapped to recordCompany
```

## # # Changing the validation strategy

By default, the package will only validate Requests when creating a data object it is possible to change the validation

strategy to always validate for each type:

```
SongData::factory()->alwaysValidate()->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

Or completely disable validation:

```
SongData::factory()->withoutValidation()->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

## # # Disabling magic methods

A data object can be created

using magic methods , this can be disabled

when using a factory:

```
SongData::factory()->withoutMagicalCreation()->from('Never gonna give you up');// Won't work since the magical method creation is disabled
```

It is also possible to ignore the magical creation methods when creating a data object as such:

```
SongData::factory()->ignoreMagicalMethod('fromString')->from('Never gonna give you up');// Won't work since the magical method is ignored
```

## # # Disabling optional values

When creating a data object that has optional properties, it is possible choose whether missing properties from the payload should be created as Optional. This can be helpful when you want to have a null value instead of an Optional object - for example, when creating the DTO from an Eloquent model with null values.

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,publicOptional|null|string$album,) {
    }
}SongData::factory()
    ->withoutOptionalValues()
    ->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);// album will `null` instead of `Optional`
```

Note that when an Optional property has no default value, and is not nullable, and the payload does not contain a value for this property, the DTO will not have the property set - so accessing it can throw Typed property must not be accessed before initialization error. Therefore, it's advisable to either set a default value or make the property nullable, when using withoutOptionalValues.

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,publicOptional|string$album,// careful here!publicOptional|string$publisher= 'unknown',publicOptional|string|null$label,) {
    }
}$data=SongData::factory()
    ->withoutOptionalValues()
    ->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);$data->toArray();// ['title' => 'Never gonna give you up', 'artist' => 'Rick Astley', 'publisher' => 'unknown', 'label' => null]$data->album;// accessing the album will throw an error, unless the property is set before accessing it
```

## # # Adding additional global casts

When creating a data object, it is possible to add additional casts to the data object:

```
SongData::factory()->withCast('string',StringToUpperCast::class)->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

These casts will not replace the other global casts defined in the data.php config file, they will though run before

the other global casts. You define them just like you would define them in the config file, the first parameter is the

type of the property that should be cast and the second parameter is the cast class.

## # # Using the creation context

Internally the package uses a creation context to create data objects. The factory allows you to use this context manually, but when using the from method it will be used automatically.

It is possible to inject the creation context into a magical method by adding it as a parameter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromModel(Song$song,CreationContext$context):self{// Do something with the context}
}
```

You can read more about creation contexts here.

Injecting property values

Introduction






# Injecting property values

### On this page

1. Filling properties from a route parameter
2. Filling properties from route parameter properties
3. Route parameters take priority over request body
4. Filling properties from the authenticated user
5. Filling properties from the container
6. Creating your own injectable attributes

When creating a data object, it is possible to inject values into properties from all kinds of sources like route

parameters, the current user or dependencies in the container.

## # # Filling properties from a route parameter

When creating data objects from requests, it's possible to automatically fill data properties from request route

parameters, such as route models.

The FromRouteParameter attribute allows filling properties with route parameter values.

### # # Using scalar route parameters

```
Route::patch('/songs/{songId}', [SongController::class,'update']);classSongDataextendsData{#[FromRouteParameter('songId')]publicint$id;publicstring$name;
}
```

Here, the $id property will be filled with the songId route parameter value (which most likely is a string or

integer).

### # # Using Models, objects or arrays as route parameters

Given that we have a route to create songs for a specific author, and that the route parameter uses route

model binding to automatically bind to an Author model:

```
Route::post('/songs/{artist}', [SongController::class,'store']);classSongDataextendsData{publicint$id;#[FromRouteParameter('artist')]publicArtistData$author;
}
```

Here, the $artist property will be filled with the artist route parameter value, which will be an instance of the

Artist model. Note that the package will automatically cast the model to ArtistData.

## # # Filling properties from route parameter properties

The FromRouteParameterProperty attribute allows filling properties with values from route parameter properties. The

main difference from FromRouteParameter is that the former uses the full route parameter value, while

FromRouteParameterProperty uses a single property from the route parameter.

In the example below, we're using route model binding. represents an instance of the Song model.

FromRouteParameterProperty automatically attempts to fill the SongData $id property from $song-&gt;id.

```
Route::patch('/songs/{song}', [SongController::class,'update']);classSongDataextendsData{#[FromRouteParameterProperty('song')]publicint$id;publicstring$name;
}
```

### # # Using custom property mapping

In the example below, $name property will be filled with $song-&gt;title (instead of `$song-&gt;name).

```
Route::patch('/songs/{song}', [SongController::class,'update']);classSongDataextendsData{#[FromRouteParameterProperty('song')]publicint$id;#[FromRouteParameterProperty('song','title')]publicstring$name;
}
```

### # # Nested property mapping

Nested properties are supported as well. Here, we fill $singerName from $artist-&gt;leadSinger-&gt;name:

```
Route::patch('/artists/{artist}/songs/{song}', [SongController::class,'update']);classSongDataextendsData{#[FromRouteParameterProperty('song')]publicint$id;#[FromRouteParameterProperty('artist','leadSinger.name')]publicstring$singerName;
}
```

## # # Route parameters take priority over request body

By default, route parameters take priority over values in the request body. For example, when the song ID is present in

the route model as well as request body, the ID from route model is used.

```
Route::patch('/songs/{song}', [SongController::class,'update']);// PATCH /songs/123// { "id": 321, "name": "Never gonna give you up" }classSongDataextendsData{#[FromRouteParameterProperty('song')]publicint$id;publicstring$name;
}
```

Here, $id will be 123 even though the request body has 321 as the ID value.

In most cases, this is useful - especially when you need the ID for a validation rule. However, there may be cases when

the exact opposite is required.

The above behavior can be turned off by switching the replaceWhenPresentInPayload flag off. This can be useful when

you intend to allow updating a property that is present in a route parameter, such as a slug:

```
Route::patch('/songs/{slug}', [SongController::class,'update']);// PATCH /songs/never// { "slug": "never-gonna-give-you-up", "name": "Never gonna give you up" }classSongDataextendsData{#[FromRouteParameter('slug',replaceWhenPresentInPayload:false)]publicstring$slug;
}
```

Here, $slug will be never-gonna-give-you-up even though the route parameter value is never.

## # # Filling properties from the authenticated user

The FromCurrentUser attribute allows filling properties with values from the authenticated user.

```
classSongDataextendsData{#[FromAuthenticatedUser]publicUserData$user;
}
```

It is possible to specify the guard to use when fetching the user:

```
classSongDataextendsData{#[FromAuthenticatedUser('api')]publicUserData$user;
}
```

Just like with route parameters, it is possible to fill properties with specific user properties using

FromAuthenticatedUserProperty:

```
classSongDataextendsData{#[FromAuthenticatedUserProperty('api','name')]publicstring$username;
}
```

All the other features like custom property mapping and not replacing values when present in the payload are supported

as well.

## # # Filling properties from the container

The FromContainer attribute allows filling properties with dependencies from the container.

```
classSongDataextendsData{#[FromContainer(SongService::class)]publicSongService$song_service;
}
```

When a dependency requires additional parameters these can be provided as such:

```
classSongDataextendsData{#[FromContainer(SongService::class,parameters: ['year'=> 1984])]publicSongService$song_service;
}
```

It is even possible to completely inject the container itself:

```
classSongDataextendsData{#[FromContainer]publicContainer$container;
}
```

Selecting a property from a dependency can be done using FromContainerProperty:

```
classSongDataextendsData{#[FromContainerProperty(SongService::class,'name')]publicstring$service_name;
}
```

Again, all the other features like custom property mapping and not replacing values when present in the payload are

supported as well.

## # # Creating your own injectable attributes

All the attributes we saw earlier implement the InjectsPropertyValue interface:

```
interfaceInjectsPropertyValue{publicfunctionresolve(DataProperty$dataProperty,mixed$payload,array$properties,CreationContext$creationContext):mixed;publicfunctionshouldBeReplacedWhenPresentInPayload() :bool;
}
```

It is possible to create your own attribute by implementing this interface. The resolve method is responsible for

returning the value that should be injected into the property. The shouldBeReplacedWhenPresentInPayload method should

return true if the value should be replaced when present in the payload.

From a model

Factories






# Mapping property names

### On this page

1. Mapping Nested Properties

Sometimes the property names in the array from which you're creating a data object might be different. You can define another name for a property when it is created from an array using attributes:

```
classContractDataextendsData{publicfunction__construct(publicstring$name,#[MapInputName('record_company')]publicstring$recordCompany,) {
    }
}
```

Creating the data object can now be done as such:

```
ContractData::from(['name'=>'Rick Astley','record_company'=>'RCA Records']);
```

Changing all property names in a data object to snake_case in the data the object is created from can be done as such:

```
#[MapInputName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

You can also use the MapName attribute when you want to combine input (see transforming data objects) and output property name mapping:

```
#[MapName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

It is possible to set a default name mapping strategy for all data objects in the data.php config file:

```
'name_mapping_strategy'=> ['input'=>SnakeCaseMapper::class,'output'=>null,
],
```

## # # Mapping Nested Properties

You can also map nested properties using dot notation in the MapInputName attribute. This is useful when you want to extract a nested value from an array and assign it to a property in your data object:

```
classSongDataextendsData{publicfunction__construct(#[MapInputName("title.name")]publicstring$title,#[MapInputName("artists.0.name")]publicstring$artist) {
    }
}
```

You can create the data object from an array with nested structures:

```
SongData::from(["title"=> ["name"=>"Never gonna give you up"],"artists"=> [
        ["name"=>"Rick Astley"]
    ]
]);
```

The package has a set of default mappers available, you can find them here.

Optional properties

Default values






# From a model

### On this page

1. Casts
2. Attributes &amp;amp; Accessors
3. Mapping property names
4. Relations
5. Missing attributes

It is possible to create a data object from a model, let's say we have the following model:

```
classArtistextendsModel{

}
```

It has the following columns in the database:

- id
- first_name
- last_name
- created_at
- updated_at

We can create a data object from this model like this:

```
classArtistDataextendsData{publicint$id;publicstring$first_name;publicstring$last_name;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

We now can create a data object from the model like this:

```
$artist=ArtistData::from(Artist::find(1));
```

## # # Casts

A model can have casts, these casts will be called before a data object is created. Let's extend the model:

```
classArtistextendsModel{publicfunctioncasts():array{return['properties'=>'array'];
    }
}
```

Within the database the new column will be stored as a JSON string, but in the data object we can just use the array

type:

```
classArtistDataextendsData{publicint$id;publicstring$first_name;publicstring$last_name;publicarray$properties;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

## # # Attributes &amp; Accessors

Laravel allows you to define attributes on a model, these will be called before a data object is created. Let's extend

the model:

```
classArtistextendsModel{publicfunctiongetFullNameAttribute():string{return$this->first_name.' '.$this->last_name;
    }
}
```

We now can use the attribute in the data object:

```
classArtistDataextendsData{publicint$id;publicstring$full_name;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

Remember: we need to use the snake_case version of the attribute in the data object since that's how it is stored in the

model. Read on for a more elegant solution when you want to use camelCase property names in your data object.

It is also possible to define accessors on a model which are the successor of the attributes:

```
classArtistextendsModel{publicfunctiongetFullName():Attribute{returnAttribute::get(fn() =>"{$this->first_name} {$this->last_name}");
    }
}
```

With the same data object we created earlier we can now use the accessor.

## # # Mapping property names

Sometimes you want to use camelCase property names in your data object, but the model uses snake_case. You can use

an MapInputName to map the property names:

```
useSpatie\LaravelData\Attributes\MapInputName;useSpatie\LaravelData\Mappers\SnakeCaseMapper;classArtistDataextendsData{publicint$id;#[MapInputName(SnakeCaseMapper::class)]publicstring$fullName;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

An even more elegant solution would be to map every property within the data object:

```
#[MapInputName(SnakeCaseMapper::class)]classArtistDataextendsData{publicint$id;publicstring$fullName;publicCarbonImmutable$createdAt;publicCarbonImmutable$updatedAt;
}
```

## # # Relations

Let's create a new model:

```
classSongextendsModel{publicfunctionartist():BelongsTo{return$this->belongsTo(Artist::class);
    }
}
```

Which has the following columns in the database:

- id
- artist_id
- title

We update our previous model as such:

```
classArtistextendsModel{publicfunctionsongs():HasMany{return$this->hasMany(Song::class);
    }
}
```

We can now create a data object like this:

```
classSongDataextendsData{publicint$id;publicstring$title;
}
```

And update our previous data object like this:

```
classArtistDataextendsData{publicint$id;/**@vararray<SongData>*/publicarray$songs;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

We can now create a data object with the relations like this:

```
$artist=ArtistData::from(Artist::with('songs')->find(1));
```

When you're not loading the relations in advance, null will be returned for the relation.

It is however possible to load the relation on the fly by adding the LoadRelation attribute to the property:

```
classArtistDataextendsData{publicint$id;/**@vararray<SongData>*/#[LoadRelation]publicarray$songs;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

Now the data object with relations can be created like this:

```
$artist=ArtistData::from(Artist::find(1));
```

We even eager-load the relation for performance, neat!

### # # Be careful with automatic loading of relations

Let's update the SongData class like this:

```
classSongDataextendsData{publicint$id;publicstring$title;#[LoadRelation]publicArtistData$artist;
}
```

When we now create a data object like this:

```
$song=SongData::from(Song::find(1));
```

We'll end up in an infinite loop, since the SongData class will try to load the ArtistData class, which will try to

load the SongData class, and so on.

## # # Missing attributes

When a model is missing attributes and preventAccessingMissingAttributes is enabled for a model the MissingAttributeException won't be thrown when creating a data object with a property that can be null or Optional.

From a request

Injecting property values






# Nesting

### On this page

1. Collections of data objects

It is possible to nest multiple data objects:

```
classArtistDataextendsData{publicfunction__construct(publicstring$name,publicint$age,) {
    }
}classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicArtistData$artist,) {
    }
}
```

You can now create a data object as such:

```
newAlbumData('Never gonna give you up',newArtistData('Rick Astley', 22)
);
```

Or you could create it from an array using a magic creation method:

```
AlbumData::from(['title'=>'Never gonna give you up','artist'=> ['name'=>'Rick Astley','age'=> 22
    ]
]);
```

## # # Collections of data objects

What if you want to nest a collection of data objects within a data object?

That's perfectly possible, but there's a small catch; you should always define what kind of data objects will be stored

within the collection. This is really important later on to create validation rules for data objects or partially

transforming data objects.

There are a few different ways to define what kind of data objects will be stored within a collection. You could use an

annotation, for example, which has an advantage that your IDE will have better suggestions when working with the data

object. And as an extra benefit, static analyzers like PHPStan will also be able to detect errors when your code

is using the wrong types.

A collection of data objects defined by annotation looks like this:

```
/**
 *@property\App\Data\SongData[]$songs*/classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicarray$songs,) {
    }
}
```

or like this when using properties:

```
classAlbumDataextendsData{publicstring$title;/**@var\App\Data\SongData[]*/publicarray$songs;
}
```

If you've imported the data class you can use the short notation:

```
useApp\Data\SongData;classAlbumDataextendsData{/**@varSongData[]*/publicarray$songs;
}
```

It is also possible to use generics:

```
useApp\Data\SongData;classAlbumDataextendsData{/**@vararray<SongData>*/publicarray$songs;
}
```

The same is true for Laravel collections, but be sure to use two generic parameters to describe the collection. One for the collection key type and one for the data object type.

```
useApp\Data\SongData;useIlluminate\Support\Collection;classAlbumDataextendsData{/**@varCollection<int, SongData>*/publicCollection$songs;
}
```

If the collection is well-annotated, the Data class doesn't need to use annotations:

```
/**
 *@templateTKeyof array-key
 *@templateTDataof \App\Data\SongData
 *
 *@extends\Illuminate\Support\Collection<TKey,TData>
 */classSongDataCollectionextendsCollection{
}classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicSongDataCollection$songs,) {
    }
}
```

You can also use an attribute to define the type of data objects that will be stored within a collection:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,#[DataCollectionOf(SongData::class)]publicarray$songs,) {
    }
}
```

This was the old way to define the type of data objects that will be stored within a collection. It is still supported, but we recommend using the annotation.

Creating a data object

Collections






# Optional properties

Sometimes you have a data object with properties which shouldn't always be set, for example in a partial API update where you only want to update certain fields. In this case you can make a property Optional as such:

```
useSpatie\LaravelData\Optional;classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring|Optional$artist,) {
    }
}
```

You can now create the data object as such:

```
SongData::from(['title'=>'Never gonna give you up']);
```

The value of artist will automatically be set to Optional. When you transform this data object to an array, it will look like this:

```
['title'=>'Never gonna give you up']
```

You can manually use Optional values within magical creation methods as such:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring|Optional$artist,) {
    }publicstaticfunctionfromTitle(string$title):static{returnnewself($title,Optional::create());
    }
}
```

It is possible to automatically update Optional values to null:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicOptional|null|string$artist,) {
    }
}SongData::factory()
    ->withoutOptionalValues()
    ->from(['title'=>'Never gonna give you up']);// artist will `null` instead of `Optional`
```

You can read more about this here.

Casts

Mapping property names






# From a request

### On this page

1. Getting the data object filled with request data from anywhere
2. Validating a collection of data objects:

You can create a data object by the values given in the request.

For example, let's say you send a POST request to an endpoint with the following data:

```
{"title":"Never gonna give you up","artist":"Rick Astley"}
```

This package can automatically resolve a SongData object from these values by using the SongData class we saw in an

earlier chapter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

You can now inject the SongData class in your controller. It will already be filled with the values found in the

request.

```
classUpdateSongController{publicfunction__invoke(Song$model,SongData$data){$model->update($data->all());returnredirect()->back();
    }
}
```

As an added benefit, these values will be validated before the data object is created. If the validation fails, a ValidationException will be thrown which will look like you've written the validation rules yourself.

The package will also automatically validate all requests when passed to the from method:

```
classUpdateSongController{publicfunction__invoke(Song$model,SongRequest$request){$model->update(SongData::from($request)->all());returnredirect()->back();
    }
}
```

We have a complete section within these docs dedicated to validation, you can find it here.

## # # Getting the data object filled with request data from anywhere

You can resolve a data object from the container.

```
app(SongData::class);
```

We resolve a data object from the container, its properties will already be filled by the values of the request with matching key names.

If the request contains data that is not compatible with the data object, a validation exception will be thrown.

## # # Validating a collection of data objects:

Let's say we want to create a data object like this from a request:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,#[DataCollectionOf(SongData::class)]publicDataCollection$songs,) {
    }
}
```

Since the SongData has its own validation rules, the package will automatically apply them when resolving validation

rules for this object.

In this case the validation rules for AlbumData would look like this:

```
['title'=> ['required','string'],'songs'=> ['required','array'],'songs.*.title'=> ['required','string'],'songs.*.artist'=> ['required','string'],
]
```

Computed values

From a model
