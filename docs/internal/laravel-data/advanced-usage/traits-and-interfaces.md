# Traits and interfaces

Laravel data, is built to be as flexible as possible. This means that you can use it in any way you want.

For example, the Data class we've been using throughout these docs is a class implementing a few data interfaces and traits:

```
useIlluminate\Contracts\Support\Responsable;useSpatie\LaravelData\Concerns\AppendableData;useSpatie\LaravelData\Concerns\BaseData;useSpatie\LaravelData\Concerns\ContextableData;useSpatie\LaravelData\Concerns\EmptyData;useSpatie\LaravelData\Concerns\IncludeableData;useSpatie\LaravelData\Concerns\ResponsableData;useSpatie\LaravelData\Concerns\TransformableData;useSpatie\LaravelData\Concerns\ValidateableData;useSpatie\LaravelData\Concerns\WrappableData;useSpatie\LaravelData\Contracts\AppendableDataasAppendableDataContract;useSpatie\LaravelData\Contracts\BaseDataasBaseDataContract;useSpatie\LaravelData\Contracts\EmptyDataasEmptyDataContract;useSpatie\LaravelData\Contracts\IncludeableDataasIncludeableDataContract;useSpatie\LaravelData\Contracts\ResponsableDataasResponsableDataContract;useSpatie\LaravelData\Contracts\TransformableDataasTransformableDataContract;useSpatie\LaravelData\Contracts\ValidateableDataasValidateableDataContract;useSpatie\LaravelData\Contracts\WrappableDataasWrappableDataContract;abstractclassDataimplementsResponsable, AppendableDataContract, BaseDataContract, TransformableDataContract, IncludeableDataContract, ResponsableDataContract, ValidateableDataContract, WrappableDataContract, EmptyDataContract{useResponsableData;useIncludeableData;useAppendableData;useValidateableData;useWrappableData;useTransformableData;useBaseData;useEmptyData;useContextableData;
}
```

These traits and interfaces allow you to create your own versions of the base Data class, and add your own functionality to it.

An example of such custom base data classes are the Resource and Dto class.

Each interface (and corresponding trait) provides a piece of functionality:

- BaseData provides the base functionality of the data package to create data objects
- BaseDataCollectable provides the base functionality of the data package to create data collections
- ContextableData provides the functionality to add context for includes and wraps to the data object/collectable
- IncludeableData provides the functionality to add includes, excludes, only and except to the data object/collectable
- TransformableData provides the functionality to transform the data object/collectable
- ResponsableData provides the functionality to return the data object/collectable as a response
- WrappableData provides the functionality to wrap the transformed data object/collectable
- AppendableData provides the functionality to append data to the transformed data payload
- EmptyData provides the functionality to get an empty version of the data object
- ValidateableData provides the functionality to validate the data object
- DeprecatableData provides the functionality to add deprecated functionality to the data object

Commands

In Packages

Help us improve this page

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