# Quickstart

### On this page

1. Using requests
2. Casting data
3. Customizing the creation of a data object
4. Nesting data objects and arrays of data objects
5. Usage in controllers
6. Using transformers
7. Generating a blueprint
8. Lazy properties
9. Conclusion

In this quickstart, we'll guide you through the most important functionalities of the package and how to use them.

First, you should install the package.

We will create a blog with different posts, so let's start with the PostData object. A post has a title, some content, a status and a date when it was published:

```
useSpatie\LaravelData\Data;classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,public?CarbonImmutable$published_at) {
    }
}
```

Extending your data objects from the base Data object is the only requirement for using the package. We add the requirements for a post as public properties.

The PostStatus is a native enum:

```
enumPostStatus:string{casedraft='draft';casepublished='published';casearchived='archived';
}
```

We store this PostData object as app/Data/PostData.php, so we have all our data objects bundled in one directory, but you're free to store them wherever you want within your application.

Tip: you can also quickly make a data object using the CLI: php artisan make:data Post, it will create a file app/Data/PostData.php.

We can now create a PostData object just like any plain PHP object:

```
$post=newPostData('Hello laravel-data','This is an introduction post for the new package',PostStatus::published,CarbonImmutable::now()
);
```

The package also allows you to create these data objects from any type, for example, an array:

```
$post=PostData::from(['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>PostStatus::published,'published_at'=>CarbonImmutable::now(),
]);
```

Or a Post model with the required properties:

```
classPostextendsModel{protected$guarded= [];protected$casts= ['status'=>PostStatus::class,'published_at'=>'immutable_datetime',
    ];
}
```

Can be quickly transformed into a PostData object:

```
PostData::from(Post::findOrFail($id));
```

## # # Using requests

Let's say we have a Laravel request coming from the frontend with these properties. Our controller would then validate these properties, and then it would store them in a model; this can be done as such:

```
classDataController{publicfunction__invoke(Request$request)
    {$request->validate($this->rules());$postData=PostData::from(['title'=>$request->input('title'),'content'=>$request->input('content'),'status'=>$request->enum('status',PostStatus::class),'published_at'=>$request->has('published_at')?CarbonImmutable::createFromFormat(DATE_ATOM,$request->input('published_at'))
                :null,
        ]);Post::create($postData->toArray());returnredirect()->back();
    }privatefunctionrules():array{return['title'=> ['required','string'],'content'=> ['required','string'],'status'=> ['required',newEnum(PostStatus::class)],'published_at'=> ['nullable','date'],
        ];
    }
}
```

That's a lot of code to fill a data object, using laravel data we can remove a lot of code:

```
classDataController{publicfunction__invoke(PostData$postData)
    {Post::create($postData->toArray());returnredirect()->back();
    }
}
```

Let's see what's happening:

1. Laravel boots up, and the router directs to the DataController
2. Because we're injecting PostData, two things happen
    - PostData will generate validation rules based on the property types and validate the request
    - The PostData object is automatically created from the request
3. We're now in the \_\_invoke method with a valid PostData object

You can always check the generated validation rules of a data object like this:

```
classDataController{publicfunction__invoke(Request$request)
    {dd(PostData::getValidationRules($request->toArray()));
    }
}
```

Which provides us with the following set of rules:

```
array:4 ["title"=> array:2 [
    0 =>"required"1 =>"string"]"content"=> array:2 [
    0 =>"required"1 =>"string"]"status"=> array:2 [
    0 =>"required"1 => Illuminate\Validation\Rules\Enum {
      #type:"App\Enums\PostStatus"}
  ]"published_at"=> array:1 [
    0 =>"nullable"]
]
```

As you can see, we're missing the date rule on the published\_at property. By default, this package will automatically generate the following rules:

- required when a property cannot be null
- nullable when a property can be null
- numeric when a property type is int
- string when a property type is string
- boolean when a property type is bool
- numeric when a property type is float
- array when a property type is array
- enum:* when a property type is a native enum

You can read more about the process of automated rule generation here.

We can easily add the date rule by using an attribute to our data object:

```
classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,#[Date]public?CarbonImmutable$published_at) {
    }
}
```

Now our validation rules look like this:

```
array:4 ["title"=> array:2 [
    0 =>"required"1 =>"string"]"content"=> array:2 [
    0 =>"required"1 =>"string"]"status"=> array:2 [
    0 =>"required"1 => Illuminate\Validation\Rules\Enum {
      #type:"App\Enums\PostStatus"}
  ]"published_at"=> array:2 [
    0 =>"nullable"1 =>"date"]
]
```

There are tons of validation rule attributes you can add to data properties. There's still much more you can do with validating data objects. Read more about it here.

Tip: By default, when creating a data object in a non request context, no validation is executed:

```
$post=PostData::from([// As long as PHP accepts the values for the properties, the object will be created]);
```

You can create validated objects without requests like this:

```
$post=PostData::validateAndCreate([// Before creating the object, each value will be validated]);
```

## # # Casting data

Let's send the following payload to the controller:

```
{"title":"Hello laravel-data","content":"This is an introduction post for the new package","status":"published","published_at":"2021-09-24T13:31:20+00:00"}
```

We get the PostData object populated with the values in the JSON payload, neat! But how did the package convert the published\_at string into a CarbonImmutable object?

It is possible to define casts within the data.php config file. By default, the casts list looks like this:

```
'casts'=> [DateTimeInterface::class=>Spatie\LaravelData\Casts\DateTimeInterfaceCast::class,
],
```

This code means that if a class property is of type DateTime, Carbon, CarbonImmutable, ... it will be automatically cast.

You can create your own casts; read more about it here.

### # # Local casts

Sometimes you need one specific cast in one specific data object; in such a case defining a local cast specific for the data object is a good option.

Let's say we have an Image class:

```
classImage{publicfunction__construct(publicstring$file,publicint$size,) {
    }
}
```

There are two options how an Image can be created:

a) From a file upload

b) From an array when the image has been stored in the database

Let's create a cast for this:

```
useIlluminate\Http\UploadedFile;useSpatie\LaravelData\Casts\Cast;useSpatie\LaravelData\Casts\Uncastable;useSpatie\LaravelData\Support\DataProperty;useStr;classImageCastimplementsCast{publicfunctioncast(DataProperty$property,mixed$value,array$properties,CreationContext$context):Image|Uncastable{// Scenario Aif($valueinstanceofUploadedFile) {$filename=$value->store('images','public');returnnewImage($filename,$value->getSize(),
            );
        }// Scenario Bif(is_array($value)) {returnnewImage($value['filename'],$value['size'],
            );
        }returnUncastable::create();
    }
}
```

Ultimately, we return Uncastable, telling the package to try other casts (if available) because this cast cannot cast the value.

The last thing we need to do is add the cast to our property. We use the WithCast attribute for this:

```
classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,#[WithCast(ImageCast::class)]public?Image$image,#[Date]public?CarbonImmutable$published_at) {
    }
}
```

You can read more about casting here.

## # # Customizing the creation of a data object

We've seen the powerful from method on data objects, you can throw anything at it, and it will cast the value into a data object. But what if it can't cast a specific type, or what if you want to change how a type is precisely cast into a data object?

It is possible to manually define how a type is converted into a data object. What if we would like to support to create posts via an email syntax like this:

```
title|status|content
```

Creating a PostData object would then look like this:

```
PostData::from('Hello laravel-data|draft|This is an introduction post for the new package');
```

To make this work, we need to add a magic creation function within our data class:

```
classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,#[WithCast(ImageCast::class)]public?Image$image,#[Date]public?CarbonImmutable$published_at) {
    }publicstaticfunctionfromString(string$post):PostData{$fields=explode('|',$post);returnnewself($fields[0],$fields[2],PostStatus::from($fields[1]),null,null);
    }
}
```

Magic creation methods allow you to create data objects from any type by passing them to the from method of a data

object, you can read more about it here.

It can be convenient to transform more complex models than our Post into data objects because you can decide how a model

would be mapped onto a data object.

## # # Nesting data objects and arrays of data objects

Now that we have a fully functional post-data object. We're going to create a new data object, AuthorData, that will store the name of an author and an array of posts the author wrote:

```
useSpatie\LaravelData\Attributes\DataCollectionOf;classAuthorDataextendsData{/**
    *@paramarray<int, PostData>$posts*/publicfunction__construct(publicstring$name,publicarray$posts) {
    }
}
```

Notice that we've typed the $posts property as an array of PostData objects using a docblock.  This will be very useful later on! The package always needs to know what type of data objects are stored in an array. Of course, when you're storing other types then data objects this is not required but recommended.

We can now create an author object as such:

```
newAuthorData('Ruben Van Assche',PostData::collect([
        ['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>PostStatus::draft,
        ],
        ['title'=>'What is a data object','content'=>'How does it work','status'=>PostStatus::published,
        ],
    ])
);
```

As you can see, the collect method can create an array of the PostData objects.

But there's another way; thankfully, our from method makes this process even more straightforward:

```
AuthorData::from(['name'=>'Ruben Van Assche','posts'=> [
        ['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>PostStatus::draft,
        ],
        ['title'=>'What is a data object','content'=>'How does it work','status'=>PostStatus::published,
        ],
    ],
]);
```

The data object is smart enough to convert an array of posts into an array of post data. Mapping data coming from the front end was never that easy!

### # # Nesting objects

Nesting an individual data object into another data object is perfectly possible. Remember the Image class we created? We needed a cast for it, but it is a perfect fit for a data object; let's create it:

```
classImageDataextendsData{publicfunction__construct(publicstring$filename,publicstring$size,) {
    }publicstaticfunctionfromUploadedImage(UploadedFile$file):self{$stored=$file->store('images','public');returnnewImageData(url($stored),$file->getSize(),
        );
    }
}
```

In our ImageCast, the image could be created from a file upload or an array; we'll handle that first case with the fromUploadedImage magic method. Because Image is now ImageData, the second case is automatically handled by the package, neat!

We'll update our PostData object as such:

```
classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,public?ImageData$image,#[Date]public?CarbonImmutable$published_at) {
    }
}
```

Creating a PostData object now can be done as such:

```
returnPostData::from(['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>PostStatus::published,'image'=> ['filename'=>'images/8JQtgd0XaPtt9CqkPJ3eWFVV4BAp6JR9ltYAIKqX.png','size'=> 16524
    ],'published_at'=>CarbonImmutable::create(2020, 05, 16),
]);
```

When we create the PostData object in a controller as such:

```
publicfunction__invoke(PostData$postData)
{return$postData;
}
```

We get a validation error:

```
{"message":"The image must be an array. (and 2 more errors)","errors":{"image":["The image must be an array."],"image.filename":["The image.filename field is required."],"image.size":["The image.size field is required."]}}
```

This is a neat feature of data; it expects a nested ImageData data object when being created from the request, an array with the keys filename and size.

We can avoid this by manually defining the validation rules for this property:

```
classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,#[WithoutValidation]public?ImageData$image,#[Date]public?CarbonImmutable$published_at) {
    }publicstaticfunctionrules(ValidationContext$context):array{return['image'=> ['nullable','image'],
        ];
    }
}
```

In the rules method, we explicitly define the rules for image .Due to how this package validates data, the nested fields image.filename and image.size would still generate validation rules, thus failing the validation. The #[WithoutValidation] explicitly tells the package only the use the custom rules defined in the rules method.

## # # Usage in controllers

We've been creating many data objects from all sorts of values, time to change course and go the other way around and start transforming data objects into arrays.

Let's say we have an API controller that returns a post:

```
publicfunction__invoke()
{returnPostData::from(['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>PostStatus::published,'published_at'=>CarbonImmutable::create(2020, 05, 16),
    ]);
}
```

By returning a data object in a controller, it is automatically converted to JSON:

```
{"title":"Hello laravel-data","content":"This is an introduction post for the new package","status":"published","image": null,"published_at":"2020-05-16T00:00:00+00:00"}
```

You can also easily convert a data object into an array as such:

```
$postData->toArray();
```

Which gives you an array like this:

```
array:5 ["title"=>"Hello laravel-data""content"=>"This is an introduction post for the new package""status"=>"published""image"=>null"published_at"=>"2020-05-16T00:00:00+00:00"]
```

It is possible to transform a data object into an array and keep complex types like the PostStatus and CarbonImmutable:

```
$postData->all();
```

This will give the following array:

```
array:5 ["title"=>"Hello laravel-data""content"=>"This is an introduction post for the new package""status"=> App\Enums\PostStatus {
    +name:"published"+value:"published"}"image"=>null"published_at"=> Carbon\CarbonImmutable {
  		... 
  }
]
```

As you can see, if we transform a data object to JSON, the CarbonImmutable published at date is transformed into a string.

## # # Using transformers

A few sections ago, we used casts to cast simple types into complex types. Transformers work the other way around. They transform complex types into simple ones and transform a data object into a simpler structure like an array or JSON.

Like the DateTimeInterfaceCast, we also have a DateTimeInterfaceTransformer that converts DateTime, Carbon,... objects into strings.

This DateTimeInterfaceTransformer is registered in the data.php config file and will automatically be used when a data object needs to transform a DateTimeInterface object:

```
'transformers'=> [DateTimeInterface::class=>\Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer::class,\Illuminate\Contracts\Support\Arrayable::class=>\Spatie\LaravelData\Transformers\ArrayableTransformer::class,
],
```

Remember the image object we created earlier; we stored a file size and filename in the object. But that could be more useful; let's provide the URL to the file when transforming the object. Just like casts, transformers also can be local. Let's implement one for Image:

```
classImageTransformerimplementsTransformer{publicfunctiontransform(DataProperty$property,mixed$value,TransformationContext$context):string{if(!$valueinstanceofImage) {thrownewException("Not an image");
        }returnurl($value->filename);
    }
}
```

We can now use this transformer in the data object like this:

```
classPostDataextendsData{publicfunction__construct(publicstring$title,publicstring$content,publicPostStatus$status,#[WithCast(ImageCast::class)]#[WithTransformer(ImageTransformer::class)]public?Image$image,#[Date]public?CarbonImmutable$published_at) {
    }
}
```

In our controller, we return the object as such:

```
publicfunction__invoke()
{returnPostData::from(['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>PostStatus::published,'image'=> ['filename'=>'images/8JQtgd0XaPtt9CqkPJ3eWFVV4BAp6JR9ltYAIKqX.png','size'=> 16524
        ],'published_at'=>CarbonImmutable::create(2020, 05, 16),
    ]);
}
```

Which leads to the following JSON:

```
{"title":"Hello laravel-data","content":"This is an introduction post for the new package","status":"published","image":"http://laravel-playbox.test/images/8JQtgd0XaPtt9CqkPJ3eWFVV4BAp6JR9ltYAIKqX.png","published_at":"2020-05-16T00:00:00+00:00"}
```

You can read more about transformers here.

## # # Generating a blueprint

We can now send our posts as JSON to the front, but what if we want to create a new post? When using Inertia, for example, we might need an empty blueprint object like this that the user could fill in:

```
{"title": null,"content": null,"status": null,"image": null,"published_at": null}
```

Such an array can be generated with the empty method, which will return an empty array following the structure of your data object:

```
PostData::empty();
```

Which will return the following array:

```
['title'=>null,'content'=>null,'status'=>null,'image'=>null,'published_at'=>null,
]
```

It is possible to set the status of the post to draft by default:

```
PostData::empty(['status'=>PostStatus::draft;
]);
```

## # # Lazy properties

For the last section of this quickstart, we will look at the AuthorData object again; let's say that we want to compose a list of all the authors. What if we had 100+ authors who have all written more than 100+ posts:

```
[{"name":"Ruben Van Assche","posts":[{"title":"Hello laravel-data","content":"This is an introduction post for the new package","status":"published","image":"http://laravel-playbox.test/images/8JQtgd0XaPtt9CqkPJ3eWFVV4BAp6JR9ltYAIKqX.png","published_at":"2021-09-24T13:31:20+00:00"}// ...]},{"name":"Freek van der Herten","posts":[{"title":"Hello laravel-event-sourcing","content":"This is an introduction post for the new package","status":"published","image":"http://laravel-playbox.test/images/8JQtgd0XaPtt9CqkPJ3eWFVV4BAp6JR9ltYAIKqX.png""published_at":"2021-09-24T13:31:20+00:00"}// ...]}// ...]
```

As you can see, this will quickly be a large set of data we would send over JSON, which we don't want to do. Since each author includes his name and all the posts, he has written.

In the end, we only want something like this:

```
[{"name":"Ruben Van Assche"},{"name":"Freek van der Herten"}// ...]
```

This functionality can be achieved with lazy properties. Lazy properties are only added to a payload when we explicitly ask it. They work with closures that are executed only when this is required:

```
classAuthorDataextendsData{/**
    *@paramCollection<PostData>|Lazy$posts*/publicfunction__construct(publicstring$name,publicCollection|Lazy$posts) {
    }publicstaticfunctionfromModel(Author$author)
    {returnnewself($author->name,Lazy::create(fn() =>PostData::collect($author->posts))
        );
    }
}
```

When we now create a new author:

```
$author=Author::create(['name'=>'Ruben Van Assche']);$author->posts()->create([        
    ['title'=>'Hello laravel-data','content'=>'This is an introduction post for the new package','status'=>'draft','published_at'=>null,
    ]
]);AuthorData::from($author);
```

Transforming it into JSON looks like this:

```
{"name":"Ruben Van Assche"}
```

If we want to include the posts, the only thing we need to do is this:

```
$postData->include('posts')->toJson();
```

Which will result in this JSON:

```
{"name":"Ruben Van Assche","posts":[{"title":"Hello laravel-data","content":"This is an introduction post for the new package","status":"published","published_at":"2021-09-24T13:31:20+00:00"}]}
```

Let's take this one step further. What if we want to only include the title of each post? We can do this by making all the other properties within the post data object also lazy:

```
classPostDataextendsData{publicfunction__construct(publicstring|Lazy$title,publicstring|Lazy$content,publicPostStatus|Lazy$status,#[WithoutValidation]#[WithCast(ImageCast::class)]#[WithTransformer(ImageTransformer::class)]publicImageData|Lazy|null$image,#[Date]publicCarbonImmutable|Lazy|null$published_at) {
    }publicstaticfunctionfromModel(Post$post):PostData{returnnewself(Lazy::create(fn() =>$post->title),Lazy::create(fn() =>$post->content),Lazy::create(fn() =>$post->status),Lazy::create(fn() =>$post->image),Lazy::create(fn() =>$post->published_at)
        );
    }publicstaticfunctionrules(ValidationContext$context):array{return['image'=> ['nullable','image'],
        ];
    }
}
```

Now the only thing we need to do is include the title:

```
$postData->include('posts.title')->toJson();
```

Which will result in this JSON:

```
{"name":"Ruben Van Assche","posts":[{"title":"Hello laravel-data"}]}
```

If we also want to include the status, we can do the following:

```
$postData->include('posts.{title,status}')->toJson();
```

It is also possible to include all properties of the posts like this:

```
$postData->include('posts.*')->toJson();
```

You can do quite a lot with lazy properties like including them:

- when a model relation is loaded like Laravel API resources
- when they are requested in the URL query
- by default, with an option to exclude them

And a lot more. You can read all about it here.

## # # Conclusion

So that's it, a quick overview of this package. We barely scratched the surface of what's possible with the package. There's still a lot more you can do with data objects like:

- casting them into Eloquent models
- transforming the structure to typescript
- working with DataCollections
- optional properties not always required when creating a data object
- wrapping transformed data into keys
- mapping property names when creating or transforming a data object
- appending extra data
- including properties using the URL query string
- inertia support for lazy properties
- and so much more ... you'll find all the information here in the docs

About us

Creating a data object

Help us improve this page

### On this page

- Using requests
- Casting data
- Customizing the creation of a data object
- Nesting data objects and arrays of data objects
- Usage in controllers
- Using transformers
- Generating a blueprint
- Lazy properties
- Conclusion

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