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

When a resource is being returned from a controller, the status code of the response will automatically be set to 201 CREATED when Laravel data detects that the request's method is POST.  In all other cases, 200 OK will be returned.

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

Help us improve this page

### On this page

- Transforming empty objects
- Response status code
- Resource classes

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