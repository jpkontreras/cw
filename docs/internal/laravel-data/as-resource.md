# Appending properties

It is possible to add some extra properties to your data objects when they are transformed into a resource:

```
SongData::from(Song::first())->additional(['year'=> 1987,
]);
```

This will output the following array:

```
['name'=>'Never gonna give you up','artist'=>'Rick Astley','year'=> 1987,
]
```

When using a closure, you have access to the underlying data object:

```
SongData::from(Song::first())->additional(['slug'=>fn(SongData$songData) =>Str::slug($songData->title),
]);
```

Which produces the following array:

```
['name'=>'Never gonna give you up','artist'=>'Rick Astley','slug'=>'never-gonna-give-you-up',
]
```

It is also possible to add extra properties by overwriting the with method within your data object:

```
classSongDataextendsData{publicfunction__construct(publicint$id,publicstring$title,publicstring$artist) {
    }publicstaticfunctionfromModel(Song$song):self{returnnewself($song->id,$song->title,$song->artist);
    }publicfunctionwith()
    {return['endpoints'=> ['show'=>action([SongsController::class,'show'],$this->id),'edit'=>action([SongsController::class,'edit'],$this->id),'delete'=>action([SongsController::class,'delete'],$this->id),
            ]
        ];
    }
}
```

Now each transformed data object contains an endpoints key with all the endpoints for that data object:

```
['id'=> 1,'name'=>'Never gonna give you up','artist'=>'Rick Astley','endpoints'=> ['show'=>'https://spatie.be/songs/1','edit'=>'https://spatie.be/songs/1','delete'=>'https://spatie.be/songs/1',
    ],
]
```

Mapping property names

Wrapping






# From data to array

### On this page

1. Using collections
2. Nesting

A data object can automatically be transformed into an array as such:

```
SongData::from(Song::first())->toArray();
```

Which will output the following array:

```
['name'=>'Never gonna give you up','artist'=>'Rick Astley']
```

By default, calling toArray on a data object will recursively transform all properties to an array. This means that nested data objects and collections of data objects will also be transformed to arrays. Other complex types like Carbon, DateTime, Enums, etc... will be transformed into a string. We'll see in the transformers section how to configure and customize this behavior.

If you only want to transform a data object to an array without transforming the properties, you can call the all method:

```
SongData::from(Song::first())->all();
```

You can also manually transform a data object to JSON:

```
SongData::from(Song::first())->toJson();
```

## # # Using collections

Here's how to create a collection of data objects:

```
SongData::collect(Song::all());
```

A collection can be transformed to array:

```
SongData::collect(Song::all())->toArray();
```

Which will output the following array:

```
[
    ["name":"Never Gonna Give You Up","artist":"Rick Astley"],
    ["name":"Giving Up on Love","artist":"Rick Astley"]
]
```

## # # Nesting

It is possible to nest data objects.

```
classUserDataextendsData{publicfunction__construct(publicstring$title,publicstring$email,publicSongData$favorite_song,) {
    }publicstaticfunctionfromModel(User$user):self{returnnewself($user->title,$user->email,SongData::from($user->favorite_song)
        );
    }
}
```

When transformed to an array, this will look like the following:

```
["name":"Ruben","email":"ruben@spatie.be","favorite_song": ["name":"Never Gonna Give You Up","artist":"Rick Astley"]
]
```

You can also nest a collection of data objects:

```
classAlbumDataextendsData{/**
    *@paramCollection<int, SongData>$songs*/publicfunction__construct(publicstring$title,publicarray$songs,) {
    }publicstaticfunctionfromModel(Album$album):self{returnnewself($album->title,SongData::collect($album->songs)
        );
    }
}
```

As always, remember to type collections of data objects by annotation or the DataCollectionOf attribute, this is essential to transform these collections correctly.

Skipping validation

From data to resource






# From data to resource

### On this page

1. Transforming empty objects
2. Response status code
3. Resource classes

A data object will automatically be transformed to a JSON response when returned in a controller:

```
classSongController{publicfunctionshow(Song$model)
    {returnSongData::from($model);
    }
}
```

The JSON then will look like this:

```
{"name":"Never gonna give you up","artist":"Rick Astley"}
```

### # # Collections

Returning a data collection from the controller like this:

```
SongData::collect(Song::all());
```

Will return a collection automatically transformed to JSON:

```
[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}]
```

### # # Paginators

It is also possible to provide a paginator:

```
SongData::collect(Song::paginate());
```

The data object is smart enough to create a paginated response from this with links to the next, previous, last, ... pages:

```
{"data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}],"meta":{"current_page": 1,"first_page_url":"https://spatie.be/?page=1","from": 1,"last_page": 7,"last_page_url":"https://spatie.be/?page=7","next_page_url":"https://spatie.be/?page=2","path":"https://spatie.be/","per_page": 15,"prev_page_url": null,"to": 15,"total": 100}}
```

## # # Transforming empty objects

When creating a new model, you probably want to provide a blueprint to the frontend with the required data to create a model. For example:

```
{"name": null,"artist": null}
```

You could make each property of the data object nullable like this:

```
classSongDataextendsData{publicfunction__construct(public?string$title,public?string$artist,) {
    }// ...}
```

This approach would work, but as soon as the model is created, the properties won't be null, which doesn't follow our data model. So it is considered a bad practice.

That's why in such cases, you can return an empty representation of the data object:

```
classSongsController{publicfunctioncreate():array{returnSongData::empty();
    }
}
```

Which will output the following JSON:

```
{"name": null,"artist": null}
```

The empty method on a data object will return an array with default empty values for the properties in the data object.

It is possible to change the default values within this array by providing them in the constructor of the data object:

```
classSongDataextendsData{publicfunction__construct(publicstring$title= 'Title of the song here',publicstring$artist= "An artist",) {
   }// ...}
```

Now when we call empty, our JSON looks like this:

```
{"name":"Title of the song here","artist":"An artist"}
```

You can also pass defaults within the empty call:

```
SongData::empty(['name'=>'Title of the song here','artist'=>'An artist']);
```

Or filter the properties that should be included in the empty response:

```
SongData::empty(only: ['name']);// Will only return the `name` propertySongData::empty(except: ['name']);// Will return the `artist` property
```

## # # Response status code

When a resource is being returned from a controller, the status code of the response will automatically be set to 201 CREATED when Laravel data detects that the request's method is POST. In all other cases, 200 OK will be returned.

## # # Resource classes

To make it a bit more clear that a data object is a resource, you can use the Resource class instead of the Data class:

```
useSpatie\LaravelData\Resource;classSongResourceextendsResource{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

These resource classes have as an advantage that they won't validate data or check authorization, They are only used to transform data which makes them a bit faster.

From data to array

Mapping property names






# Including and excluding properties

### On this page

1. Including lazy properties
2. Types of Lazy properties
3. Default included lazy properties
4. Auto Lazy
5. Only and Except
6. Using query strings
7. Mutability

Sometimes you don't want all the properties included when transforming a data object to an array, for example:

```
classAlbumDataextendsData{/**
    *@paramCollection<int, SongData>$songs*/publicfunction__construct(publicstring$title,publicCollection$songs,) {
    }
}
```

This will always output a collection of songs, which can become quite large. With lazy properties, we can include

properties when we want to:

```
classAlbumDataextendsData{/**
    *@paramLazy|Collection<int, SongData>$songs*/publicfunction__construct(publicstring$title,publicLazy|Collection$songs,) {
    }publicstaticfunctionfromModel(Album$album):self{returnnewself($album->title,Lazy::create(fn() =>SongData::collect($album->songs))
        );
    }
}
```

The songs key won't be included in the resource when transforming it from a model. Because the closure that provides

the data won't be called when transforming the data object unless we explicitly demand it.

Now when we transform the data object as such:

```
AlbumData::from(Album::first())->toArray();
```

We get the following array:

```
['title'=>'Together Forever',
]
```

As you can see, the songs property is missing in the array output. Here's how you can include it.

```
AlbumData::from(Album::first())->include('songs');
```

## # # Including lazy properties

Lazy properties will only be included when the include method is called on the data object with the property's name.

It is also possible to nest these includes. For example, let's update the SongData class and make all of its

properties lazy:

```
classSongDataextendsData{publicfunction__construct(publicLazy|string$title,publicLazy|string$artist,) {
    }publicstaticfunctionfromModel(Song$song):self{returnnewself(Lazy::create(fn() =>$song->title),Lazy::create(fn() =>$song->artist)
        );
    }
}
```

Now name or artist should be explicitly included. This can be done as such on the AlbumData:

```
AlbumData::from(Album::first())->include('songs.name','songs.artist');
```

Or you could combine these includes:

```
AlbumData::from(Album::first())->include('songs.{name, artist}');
```

If you want to include all the properties of a data object, you can do the following:

```
AlbumData::from(Album::first())->include('songs.*');
```

Explicitly including properties of data objects also works on a single data object. For example, our UserData looks

like this:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,publicLazy|SongData$favorite_song,) {
    }publicstaticfunctionfromModel(User$user):self{returnnewself($user->title,Lazy::create(fn() =>SongData::from($user->favorite_song))
        );
    }
}
```

We can include properties of the data object just like we would with collections of data objects:

```
returnUserData::from(Auth::user())->include('favorite_song.name');
```

## # # Types of Lazy properties

### # # Conditional Lazy properties

You can include lazy properties in different ways:

```
Lazy::create(fn() =>SongData::collect($album->songs));
```

With a basic Lazy property, you must explicitly include it when the data object is transformed.

Sometimes you only want to include a property when a specific condition is true. This can be done with conditional lazy

properties:

```
Lazy::when(fn() =>$this->is_admin,fn() =>SongData::collect($album->songs));
```

The property will only be included when the is_admin property of the data object is true. It is not possible to

include the property later on with the include method when a condition is not accepted.

### # # Relational Lazy properties

You can also only include a lazy property when a particular relation is loaded on the model as such:

```
Lazy::whenLoaded('songs',$album,fn() =>SongData::collect($album->songs));
```

Now the property will only be included when the song's relation is loaded on the model.

## # # Default included lazy properties

It is possible to mark a lazy property as included by default:

```
Lazy::create(fn() =>SongData::collect($album->songs))->defaultIncluded();
```

The property will now always be included when the data object is transformed. You can explicitly exclude properties that

were default included as such:

```
AlbumData::create(Album::first())->exclude('songs');
```

## # # Auto Lazy

Writing Lazy properties can be a bit cumbersome. It is often a repetitive task to write the same code over and over

again while the package can infer almost everything.

Let's take a look at our previous example:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,publicLazy|SongData$favorite_song,) {
    }publicstaticfunctionfromModel(User$user):self{returnnewself($user->title,Lazy::create(fn() =>SongData::from($user->favorite_song))
        );
    }
}
```

The package knows how to get the property from the model and wrap it into a data object, but since we're using a lazy

property, we need to write our own magic creation method with a lot of repetitive code.

In such a situation auto lazy might be a good fit, instead of casting the property directly into the data object, the

casting process is wrapped in a lazy Closure.

This makes it possible to rewrite the example as such:

```
#[AutoLazy]classUserDataextendsData{publicfunction__construct(publicstring$title,publicLazy|SongData$favorite_song,) {
    }
}
```

While achieving the same result!

Auto Lazy wraps the casting process of a value for every property typed as Lazy into a Lazy Closure when the

AutoLazy attribute is present on the class.

It is also possible to use the AutoLazy attribute on a property level:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,#[AutoLazy]publicLazy|SongData$favorite_song,) {
    }
}
```

The auto lazy process won't be applied in the following situations:

- When a null value is passed to the property
- When the property value isn't present in the input payload and the property typed as Optional
- When a Lazy Closure is passed to the property

### # # Auto lazy with model relations

When you're constructing a data object from an Eloquent model, it is also possible to automatically create lazy

properties for model relations which are only resolved when the relation is loaded:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,#[AutoWhenLoadedLazy]publicLazy|SongData$favoriteSong,) {
    }
}
```

When the favoriteSong relation is loaded on the model, the property will be included in the data object.

If the name of the relation doesn't match the property name, you can specify the relation name:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,#[AutoWhenLoadedLazy('favoriteSong')]publicLazy|SongData$favorite_song,) {
    }
}
```

The package will use the regular casting process when the relation is loaded, so it is also perfectly possible to create a collection of data objects:

```
classUserDataextendsData{/**
    *@paramLazy|array<int, SongData>$favoriteSongs*/publicfunction__construct(publicstring$title,#[AutoWhenLoadedLazy]publicLazy|array$favoriteSongs,) {
    }
}
```

## # # Only and Except

Lazy properties are great for reducing payloads sent over the wire. However, when you completely want to remove a

property Laravel's only and except methods can be used:

```
AlbumData::from(Album::first())->only('songs');// will only show `songs`AlbumData::from(Album::first())->except('songs');// will show everything except `songs`
```

It is also possible to use multiple keys:

```
AlbumData::from(Album::first())->only('songs.name','songs.artist');AlbumData::from(Album::first())->except('songs.name','songs.artist');
```

And special keys like described above:

```
AlbumData::from(Album::first())->only('songs.{name, artist}');AlbumData::from(Album::first())->except('songs.{name, artist}');
```

Only and except always take precedence over include and exclude, which means that when a property is hidden by only or

except it is impossible to show it again using include.

### # # Conditionally

It is possible to add an include, exclude, only or except if a certain condition is met:

```
AlbumData::from(Album::first())->includeWhen('songs',auth()->user()->isAdmin);AlbumData::from(Album::first())->excludeWhen('songs',auth()->user()->isAdmin);AlbumData::from(Album::first())->onlyWhen('songs',auth()->user()->isAdmin);AlbumData::from(Album::first())->except('songs',auth()->user()->isAdmin);
```

You can also use the values of the data object in such condition:

```
AlbumData::from(Album::first())->includeWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0);AlbumData::from(Album::first())->excludeWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0);AlbumData::from(Album::first())->onlyWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0);AlbumData::from(Album::first())->exceptWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0);
```

In some cases, you may want to define an include on a class level by implementing a method:

```
classAlbumDataextendsData{/**
    *@paramLazy|Collection<SongData>$songs*/publicfunction__construct(publicstring$title,publicLazy|Collection$songs,) {
    }publicfunctionincludeProperties():array{return['songs'=>$this->title==='Together Forever',
        ];
    }
}
```

It is even possible to include nested properties:

```
classAlbumDataextendsData{/**
    *@paramLazy|Collection<SongData>$songs*/publicfunction__construct(publicstring$title,publicLazy|Collection$songs,) {
    }publicfunctionincludeProperties():array{return['songs.title'=>$this->title==='Together Forever',
        ];
    }
}
```

You can define exclude, except and only partials on a data class:

- You can define excludes in a excludeProperties method
- You can define except in a exceptProperties method
- You can define only in a onlyProperties method

## # # Using query strings

It is possible to include or exclude lazy properties by the URL query string:

For example, when we create a route my-account:

```
// in web.phpRoute::get('my-account',fn() =>UserData::from(User::first()));
```

We now specify that a key of the data object is allowed to be included by query string on the data object:

```
classUserDataextendsData{publicstaticfunctionallowedRequestIncludes():?array{return['favorite_song'];
    }// ...}
```

Our JSON would look like this when we request https://spatie.be/my-account:

```
{"name":"Ruben Van Assche"}
```

We can include favorite_song by adding it to the query in the URL as such:

```
https://spatie.be/my-account?include=favorite_song
```

```
{"name":"Ruben Van Assche","favorite_song":{"name":"Never Gonna Give You Up","artist":"Rick Astley"}}
```

We can also include multiple properties by separating them with a comma:

```
https://spatie.be/my-account?include=favorite_song,favorite_movie
```

Or by using a group input:

```
https://spatie.be/my-account?include[]=favorite_song&include[]=favorite_movie
```

Including properties works for data objects and data collections.

### # # Allowing includes by query string

By default, it is disallowed to include properties by query string:

```
classUserDataextendsData{publicstaticfunctionallowedRequestIncludes():?array{return[];
    }
}
```

You can pass several names of properties which are allowed to be included by query string:

```
classUserDataextendsData{publicstaticfunctionallowedRequestIncludes():?array{return['favorite_song','name'];
    }
}
```

Or you can allow all properties to be included by query string:

```
classUserDataextendsData{publicstaticfunctionallowedRequestIncludes():?array{returnnull;
    }
}
```

### # # Other operations

It is also possible to run exclude, except and only operations on a data object:

- You can define excludes in allowedRequestExcludes and use the exclude key in your query string
- You can define except in allowedRequestExcept and use the except key in your query string
- You can define only in allowedRequestOnly and use the only key in your query string

## # # Mutability

Adding includes/excludes/only/except to a data object will only affect the data object (and its nested chain) once:

```
AlbumData::from(Album::first())->include('songs')->toArray();// will include songsAlbumData::from(Album::first())->toArray();// will not include songs
```

If you want to add includes/excludes/only/except to a data object and its nested chain that will be used for all future

transformations, you can define them in their respective \*properties methods:

```
classAlbumDataextendsData{/**
    *@paramLazy|Collection<SongData>$songs*/publicfunction__construct(publicstring$title,publicLazy|Collection$songs,) {
    }publicfunctionincludeProperties():array{return['songs'];
    }
}
```

Or use the permanent methods:

```
AlbumData::from(Album::first())->includePermanently('songs');AlbumData::from(Album::first())->excludePermanently('songs');AlbumData::from(Album::first())->onlyPermanently('songs');AlbumData::from(Album::first())->exceptPermanently('songs');
```

When using a conditional includes/excludes/only/except, you can set the permanent flag:

```
AlbumData::from(Album::first())->includeWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0,permanent:true);AlbumData::from(Album::first())->excludeWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0,permanent:true);AlbumData::from(Album::first())->onlyWhen('songs',fn(AlbumData$data) =>count($data->songs) > 0),permanent:true);AlbumData::from(Album::first())->except('songs',fn(AlbumData$data) =>count($data->songs) > 0,permanent:true);
```

Wrapping

Transforming data






# Mapping property names

Sometimes you might want to change the name of a property in the transformed payload, with attributes this is possible:

```
classContractDataextendsData{publicfunction__construct(publicstring$name,#[MapOutputName('record_company')]publicstring$recordCompany,) {
    }
}
```

Now our array looks like this:

```
['name'=>'Rick Astley','record_company'=>'RCA Records',
]
```

Changing all property names in a data object to snake_case as output data can be done as such:

```
#[MapOutputName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

You can also use the MapName attribute when you want to combine input and output property name mapping:

```
#[MapName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

It is possible to set a default name mapping strategy for all data objects in the data.php config file:

```
'name_mapping_strategy'=> ['input'=>null,'output'=>SnakeCaseMapper::class,
],
```

You can now create a data object as such:

```
$contract=newContractData(name:'Rick Astley',recordCompany:'RCA Records',
);
```

And a transformed version of the data object will look like this:

```
['name'=>'Rick Astley','record_company'=>'RCA Records',
]
```

The package has a set of default mappers available, you can find them here.

From data to resource

Appending properties






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






# Wrapping

### On this page

1. Wrapping collections
2. Nested wrapping
3. Disabling wrapping
4. Getting a wrapped array

By default, when a data object is transformed into JSON in your controller, it looks like this:

```
{"name":"Never gonna give you up","artist":"Rick Astley"}
```

It is possible to wrap a data object:

```
SongData::from(Song::first())->wrap('data');
```

Now the JSON looks like this:

```
{"data":{"name":"Never gonna give you up","artist":"Rick Astley"}}
```

Data objects and collections will only get wrapped when you're sending them as a response and never when calling toArray or toJson on it.

It is possible to define a default wrap key inside a data object:

```
classSongDataextendsData{publicfunctiondefaultWrap():string{return'data';
    }// ...}
```

Or you can set a global wrap key inside the data.php config file:

```
/*
     * Data objects can be wrapped into a key like 'data' when used as a resource,
     * this key can be set globally here for all data objects. You can pass in
     * `null` if you want to disable wrapping.
     */'wrap'=>'data',
```

## # # Wrapping collections

Collections can be wrapped just like data objects:

```
SongData::collect(Song::all(),DataCollection::class)->wrap('data');
```

Notice here, for now we only support wrapping DataCollections, PaginatedDataCollections and CursorPaginatedDataCollections on the root level. Wrapping won't work for Laravel Collections or arrays (for now) since the package cannot interfere. Nested properties with such types can be wrapped though (see further).

The JSON will now look like this:

```
{"data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}]}
```

It is possible to set the data key in paginated collections:

```
SongData::collect(Song::paginate(),PaginatedDataCollection::class)->wrap('paginated_data');
```

Which will let the JSON look like this:

```
{"paginated_data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}],"meta":{"current_page": 1,"first_page_url":"https://spatie.be/?page=1","from": 1,"last_page": 7,"last_page_url":"https://spatie.be/?page=7","next_page_url":"https://spatie.be/?page=2","path":"https://spatie.be/","per_page": 15,"prev_page_url": null,"to": 15,"total": 100}}
```

## # # Nested wrapping

A data object included inside another data object will never be wrapped even if a wrap is set:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,publicstring$email,publicSongData$favorite_song,) {
    }publicstaticfunctionfromModel(User$user):self{returnnewself($user->title,$user->email,SongData::create($user->favorite_song)->wrap('data')
        );
    }
}UserData::from(User::first())->wrap('data');
```

```
{"data":{"name":"Ruben","email":"ruben@spatie.be","favorite_song":{"name":"Never Gonna Give You Up","artist":"Rick Astley"}}}
```

A data collection inside a data object will get wrapped when a wrapping key is set (in order to mimic Laravel resources):

```
useSpatie\LaravelData\Attributes\DataCollectionOf;useSpatie\LaravelData\DataCollection;classAlbumDataextendsData{publicfunction__construct(publicstring$title,#[DataCollectionOf(SongData::class)]publicDataCollection$songs,) {
    }publicstaticfunctionfromModel(Album$album):self{returnnewself($album->title,SongData::collect($album->songs,DataCollection::class)->wrap('data')
        );
    }
}AlbumData::from(Album::first())->wrap('data');
```

The JSON will look like this:

```
{"data":{"title":"Whenever You Need Somebody","songs":{"data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}]}}}
```

## # # Disabling wrapping

Whenever a data object is wrapped due to the default wrap method or a global wrap key, it is possible to disable wrapping on a data object/collection:

```
SongData::from(Song::first())->withoutWrapping();
```

## # # Getting a wrapped array

By default, toArray and toJson will never wrap a data object or collection, but it is possible to get a wrapped array:

```
SongData::from(Song::first())->wrap('data')->transform(wrapExecutionType:WrapExecutionType::Enabled);
```

Appending properties

Including and excluding properties
