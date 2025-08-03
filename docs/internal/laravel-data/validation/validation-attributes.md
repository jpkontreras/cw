# Validation attributes

### On this page

1. Accepted
2. AcceptedIf
3. ActiveUrl
4. After
5. AfterOrEqual
6. Alpha
7. AlphaDash
8. AlphaNumeric
9. ArrayType
10. Bail
11. Before
12. BeforeOrEqual
13. Between
14. BooleanType
15. Confirmed
16. CurrentPassword
17. Date
18. DateEquals
19. DateFormat
20. Declined
21. DeclinedIf
22. Different
23. Digits
24. DigitsBetween
25. Dimensions
26. Distinct
27. DoesntEndWith
28. DoesntStartWith
29. Email
30. EndsWith
31. Enum
32. ExcludeIf
33. ExcludeUnless
34. ExcludeWith
35. ExcludeWithout
36. Exists
37. File
38. Filled
39. GreaterThan
40. GreaterThanOrEqualTo
41. Image
42. In
43. InArray
44. IntegerType
45. IP
46. IPv4
47. IPv6
48. Json
49. LessThan
50. LessThanOrEqualTo
51. Lowercase
52. ListType
53. MacAddress
54. Max
55. MaxDigits
56. MimeTypes
57. Mimes
58. Min
59. MinDigits
60. MultipleOf
61. NotIn
62. NotRegex
63. Nullable
64. Numeric
65. Password
66. Present
67. Prohibited
68. ProhibitedIf
69. ProhibitedUnless
70. Prohibits
71. Regex
72. Required
73. RequiredIf
74. RequiredUnless
75. RequiredWith
76. RequiredWithAll
77. RequiredWithout
78. RequiredWithoutAll
79. RequiredArrayKeys
80. Rule
81. Same
82. Size
83. Sometimes
84. StartsWith
85. StringType
86. TimeZone
87. Unique
88. Uppercase
89. Url
90. Ulid
91. Uuid

These are all the validation attributes currently available in laravel-data.

## # # Accepted

Docs

```
#[Accepted]publicbool$closure;
```

## # # AcceptedIf

Docs

```
#[AcceptedIf('other_field','equals_this')]publicbool$closure;
```

## # # ActiveUrl

Docs

```
#[ActiveUrl]publicstring$closure;
```

## # # After

Docs

```
#[After('tomorrow')]publicCarbon$closure;#[After(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[After(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # AfterOrEqual

Docs

```
#[AfterOrEqual('tomorrow')]publicCarbon$closure;#[AfterOrEqual(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[AfterOrEqual(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # Alpha

Docs

```
#[Alpha]publicstring$closure;
```

## # # AlphaDash

Docs

```
#[AlphaDash]publicstring$closure;
```

## # # AlphaNumeric

Docs

```
#[AlphaNumeric]publicstring$closure;
```

## # # ArrayType

Docs

```
#[ArrayType]publicarray$closure;#[ArrayType(['valid_key','other_valid_key'])]publicarray$closure;#[ArrayType('valid_key','other_valid_key')]publicarray$closure;
```

## # # Bail

Docs

```
#[Bail]publicstring$closure;
```

## # # Before

Docs

```
#[Before('tomorrow')]publicCarbon$closure;#[Before(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[Before(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # BeforeOrEqual

Docs

```
#[BeforeOrEqual('tomorrow')]publicCarbon$closure;#[BeforeOrEqual(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[BeforeOrEqual(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # Between

Docs

```
#[Between(3.14, 42)]publicint$closure;
```

## # # BooleanType

Docs

```
#[BooleanType]publicbool$closure;
```

## # # Confirmed

Docs

```
#[Confirmed]publicstring$closure;
```

## # # CurrentPassword

Docs

```
#[CurrentPassword]publicstring$closure;#[CurrentPassword('api')]publicstring$closure;
```

## # # Date

Docs

```
#[Date]publicCarbon$date;
```

## # # DateEquals

Docs

```
#[DateEquals('tomorrow')]publicCarbon$date;#[DateEquals(Carbon::yesterday())]publicCarbon$date;
```

## # # DateFormat

Docs

```
#[DateFormat('d-m-Y')]publicCarbon$date;#[DateFormat(['Y-m-d','Y-m-d H:i:s'])]publicCarbon$date;
```

## # # Declined

Docs

```
#[Declined]publicbool$closure;
```

## # # DeclinedIf

Docs

```
#[DeclinedIf('other_field','equals_this')]publicbool$closure;
```

## # # Different

Docs

```
#[Different('other_field')]publicstring$closure;
```

## # # Digits

Docs

```
#[Digits(10)]publicint$closure;
```

## # # DigitsBetween

Docs

```
#[DigitsBetween(2, 10)]publicint$closure;
```

## # # Dimensions

Docs

```
#[Dimensions(ratio: 1.5)]publicUploadedFile$closure;#[Dimensions(maxWidth: 100,maxHeight: 100)]publicUploadedFile$closure;
```

## # # Distinct

Docs

```
#[Distinct]publicstring$closure;#[Distinct(Distinct::Strict)]publicstring$closure;#[Distinct(Distinct::IgnoreCase)]publicstring$closure;
```

## # # DoesntEndWith

Docs

```
#[DoesntEndWith('a')]publicstring$closure;#[DoesntEndWith(['a','b'])]publicstring$closure;#[DoesntEndWith('a','b')]publicstring$closure;
```

## # # DoesntStartWith

Docs

```
#[DoesntStartWith('a')]publicstring$closure;#[DoesntStartWith(['a','b'])]publicstring$closure;#[DoesntStartWith('a','b')]publicstring$closure;
```

## # # Email

Docs

```
#[Email]publicstring$closure;#[Email(Email::RfcValidation)]publicstring$closure;#[Email([Email::RfcValidation,Email::DnsCheckValidation])]publicstring$closure;#[Email(Email::RfcValidation,Email::DnsCheckValidation)]publicstring$closure;
```

## # # EndsWith

Docs

```
#[EndsWith('a')]publicstring$closure;#[EndsWith(['a','b'])]publicstring$closure;#[EndsWith('a','b')]publicstring$closure;
```

## # # Enum

Docs

```
#[Enum(ChannelType::class)]publicstring$closure;#[Enum(ChannelType::class,only: [ChannelType::Email])]publicstring$closure;#[Enum(ChannelType::class,except: [ChannelType::Email])]publicstring$closure;
```

## # # ExcludeIf

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeIf('other_field','has_value')]publicstring$closure;
```

## # # ExcludeUnless

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeUnless('other_field','has_value')]publicstring$closure;
```

## # # ExcludeWith

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeWith('other_field')]publicstring$closure;
```

## # # ExcludeWithout

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeWithout('other_field')]publicstring$closure;
```

## # # Exists

Docs

```
#[Exists('users')]publicstring$closure;#[Exists(User::class)]publicstring$closure;#[Exists('users','email')]publicstring$closure;#[Exists('users','email',connection:'tenant')]publicstring$closure;#[Exists('users','email',withoutTrashed:true)]publicstring$closure;
```

## # # File

Docs

```
#[File]publicUploadedFile$closure;
```

## # # Filled

Docs

```
#[Filled]publicstring$closure;
```

## # # GreaterThan

Docs

```
#[GreaterThan('other_field')]publicint$closure;
```

## # # GreaterThanOrEqualTo

Docs

```
#[GreaterThanOrEqualTo('other_field')]publicint$closure;
```

## # # Image

Docs

```
#[Image]publicUploadedFile$closure;
```

## # # In

Docs

```
#[In([1, 2, 3,'a','b'])]publicmixed$closure;#[In(1, 2, 3,'a','b')]publicmixed$closure;
```

## # # InArray

Docs

```
#[InArray('other_field')]publicstring$closure;
```

## # # IntegerType

Docs

```
#[IntegerType]publicint$closure;
```

## # # IP

Docs

```
#[IP]publicstring$closure;
```

## # # IPv4

Docs

```
#[IPv4]publicstring$closure;
```

## # # IPv6

Docs

```
#[IPv6]publicstring$closure;
```

## # # Json

Docs

```
#[Json]publicstring$closure;
```

## # # LessThan

Docs

```
#[LessThan('other_field')]publicint$closure;
```

## # # LessThanOrEqualTo

Docs

```
#[LessThanOrEqualTo('other_field')]publicint$closure;
```

## # # Lowercase

Docs

```
#[Lowercase]publicstring$closure;
```

## # # ListType

Docs

```
#[ListType]publicarray$array;
```

## # # MacAddress

Docs

```
#[MacAddress]publicstring$closure;
```

## # # Max

Docs

```
#[Max(20)]publicint$closure;
```

## # # MaxDigits

Docs

```
#[MaxDigits(10)]publicint$closure;
```

## # # MimeTypes

Docs

```
#[MimeTypes('video/quicktime')]publicUploadedFile$closure;#[MimeTypes(['video/quicktime','video/avi'])]publicUploadedFile$closure;#[MimeTypes('video/quicktime','video/avi')]publicUploadedFile$closure;
```

## # # Mimes

Docs

```
#[Mimes('jpg')]publicUploadedFile$closure;#[Mimes(['jpg','png'])]publicUploadedFile$closure;#[Mimes('jpg','png')]publicUploadedFile$closure;
```

## # # Min

Docs

```
#[Min(20)]publicint$closure;
```

## # # MinDigits

Docs

```
#[MinDigits(2)]publicint$closure;
```

## # # MultipleOf

Docs

```
#[MultipleOf(3)]publicint$closure;
```

## # # NotIn

Docs

```
#[NotIn([1, 2, 3,'a','b'])]publicmixed$closure;#[NotIn(1, 2, 3,'a','b')]publicmixed$closure;
```

## # # NotRegex

Docs

```
#[NotRegex('/^.+$/i')]publicstring$closure;
```

## # # Nullable

Docs

```
#[Nullable]public?string$closure;
```

## # # Numeric

Docs

```
#[Numeric]public?string$closure;
```

## # # Password

Docs

```
#[Password(min: 12,letters:true,mixedCase:true,numbers:false,symbols:false,uncompromised:true,uncompromisedThreshold: 0)]publicstring$closure;
```

## # # Present

Docs

```
#[Present]publicstring$closure;
```

## # # Prohibited

Docs

```
#[Prohibited]public?string$closure;
```

## # # ProhibitedIf

Docs

```
#[ProhibitedIf('other_field','has_value')]public?string$closure;#[ProhibitedIf('other_field', ['has_value','or_this_value'])]public?string$closure;
```

## # # ProhibitedUnless

Docs

```
#[ProhibitedUnless('other_field','has_value')]public?string$closure;#[ProhibitedUnless('other_field', ['has_value','or_this_value'])]public?string$closure;
```

## # # Prohibits

Docs

```
#[Prohibits('other_field')]public?string$closure;#[Prohibits(['other_field','another_field'])]public?string$closure;#[Prohibits('other_field','another_field')]public?string$closure;
```

## # # Regex

Docs

```
#[Regex('/^.+$/i')]publicstring$closure;
```

## # # Required

Docs

```
#[Required]publicstring$closure;
```

## # # RequiredIf

Docs

```
#[RequiredIf('other_field','value')]public?string$closure;#[RequiredIf('other_field', ['value','another_value'])]public?string$closure;
```

## # # RequiredUnless

Docs

```
#[RequiredUnless('other_field','value')]public?string$closure;#[RequiredUnless('other_field', ['value','another_value'])]public?string$closure;
```

## # # RequiredWith

Docs

```
#[RequiredWith('other_field')]public?string$closure;#[RequiredWith(['other_field','another_field'])]public?string$closure;#[RequiredWith('other_field','another_field')]public?string$closure;
```

## # # RequiredWithAll

Docs

```
#[RequiredWithAll('other_field')]public?string$closure;#[RequiredWithAll(['other_field','another_field'])]public?string$closure;#[RequiredWithAll('other_field','another_field')]public?string$closure;
```

## # # RequiredWithout

Docs

```
#[RequiredWithout('other_field')]public?string$closure;#[RequiredWithout(['other_field','another_field'])]public?string$closure;#[RequiredWithout('other_field','another_field')]public?string$closure;
```

## # # RequiredWithoutAll

Docs

```
#[RequiredWithoutAll('other_field')]public?string$closure;#[RequiredWithoutAll(['other_field','another_field'])]public?string$closure;#[RequiredWithoutAll('other_field','another_field')]public?string$closure;
```

## # # RequiredArrayKeys

Docs

```
#[RequiredArrayKeys('a')]publicarray$closure;#[RequiredArrayKeys(['a','b'])]publicarray$closure;#[RequiredArrayKeys('a','b')]publicarray$closure;
```

## # # Rule

```
#[Rule('string|uuid')]publicstring$closure;#[Rule(['string','uuid'])]publicstring$closure;
```

## # # Same

Docs

```
#[Same('other_field')]publicstring$closure;
```

## # # Size

Docs

```
#[Size(10)]publicstring$closure;
```

## # # Sometimes

Docs

```
#[Sometimes]publicstring$closure;
```

## # # StartsWith

Docs

```
#[StartsWith('a')]publicstring$closure;#[StartsWith(['a','b'])]publicstring$closure;#[StartsWith('a','b')]publicstring$closure;
```

## # # StringType

Docs

```
#[StringType()]publicstring$closure;
```

## # # TimeZone

Docs

```
#[TimeZone()]publicstring$closure;
```

## # # Unique

Docs

```
#[Unique('users')]publicstring$closure;#[Unique(User::class)]publicstring$closure;#[Unique('users','email')]publicstring$closure;#[Unique('users',connection:'tenant')]publicstring$closure;#[Unique('users',withoutTrashed:true)]publicstring$closure;#[Unique('users',ignore: 5)]publicstring$closure;#[Unique('users',ignore:newAuthenticatedUserReference())]publicstring$closure;#[Unique('posts',ignore:newRouteParameterReference('post'))]publicstring$closure;
```

## # # Uppercase

Docs

```
#[Uppercase]publicstring$closure;
```

## # # Url

Docs

```
#[Url]publicstring$closure;
```

## # # Ulid

Docs

```
#[Ulid]publicstring$closure;
```

## # # Uuid

Docs

```
#[Uuid]publicstring$closure;
```

Mapping rules

Get data from a class quickly

Help us improve this page

### On this page

- Accepted
- AcceptedIf
- ActiveUrl
- After
- AfterOrEqual
- Alpha
- AlphaDash
- AlphaNumeric
- ArrayType
- Bail
- Before
- BeforeOrEqual
- Between
- BooleanType
- Confirmed
- CurrentPassword
- Date
- DateEquals
- DateFormat
- Declined
- DeclinedIf
- Different
- Digits
- DigitsBetween
- Dimensions
- Distinct
- DoesntEndWith
- DoesntStartWith
- Email
- EndsWith
- Enum
- ExcludeIf
- ExcludeUnless
- ExcludeWith
- ExcludeWithout
- Exists
- File
- Filled
- GreaterThan
- GreaterThanOrEqualTo
- Image
- In
- InArray
- IntegerType
- IP
- IPv4
- IPv6
- Json
- LessThan
- LessThanOrEqualTo
- Lowercase
- ListType
- MacAddress
- Max
- MaxDigits
- MimeTypes
- Mimes
- Min
- MinDigits
- MultipleOf
- NotIn
- NotRegex
- Nullable
- Numeric
- Password
- Present
- Prohibited
- ProhibitedIf
- ProhibitedUnless
- Prohibits
- Regex
- Required
- RequiredIf
- RequiredUnless
- RequiredWith
- RequiredWithAll
- RequiredWithout
- RequiredWithoutAll
- RequiredArrayKeys
- Rule
- Same
- Size
- Sometimes
- StartsWith
- StringType
- TimeZone
- Unique
- Uppercase
- Url
- Ulid
- Uuid

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