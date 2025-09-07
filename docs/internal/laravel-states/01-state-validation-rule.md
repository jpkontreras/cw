# State validation rule

This package provides a validation rule to validate incoming request data.

```
useSpatie\ModelStates\Validation\ValidStateRule;request()->validate(['state'=>newValidStateRule(PaymentState::class),
]);// Allowing nullrequest()->validate(['state'=>ValidStateRule::make(PaymentState::class)->nullable(),
]);
```

Only valid state values of PaymentState implementations will be allowed.

State scopes

Help us improve this page

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