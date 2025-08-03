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

Given that we have a route to create songs for a specific author, and that the  route parameter uses route

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

In the example below, we're using route model binding.  represents an instance of the Song model.

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

Help us improve this page

### On this page

- Filling properties from a route parameter
- Filling properties from route parameter properties
- Route parameters take priority over request body
- Filling properties from the authenticated user
- Filling properties from the container
- Creating your own injectable attributes

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