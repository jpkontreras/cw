# Listing states

### On this page

1. Get Registered States
2. Get Default States

Say you have setup the invoice model as follows:

```
namespaceApp;useApp\States\Invoice\InvoiceState;useApp\States\Invoice\Declined;useApp\States\Invoice\Paid;useApp\States\Invoice\Pending;useApp\States\Fulfillment\FulfillmentState;useApp\States\Fulfillment\Complete;useApp\States\Fulfillment\Partial;useApp\States\Fulfillment\Unfulfilled;useIlluminate\Database\Eloquent\Model;useSpatie\ModelStates\HasStates;classInvoiceextendsModel{useHasStates;protected$casts= ['state'=>InvoiceState::class,'fulfillment'=>FulfillmentState::class,
    ];
}
```

## # # Get Registered States

You can get all the registered states with Invoice::getStates(), which returns a collection of state morph names, grouped by column:

```
["state"=> ['declined','paid','pending',
    ],"fulfillment"=> ['complete','partial','unfulfilled',
    ]
]
```

You can also get the registered states for a specific column with Invoice::getStatesFor('state'), which returns a collection of state classes:

```
['declined','paid','pending',
],
```

## # # Get Default States

You can get all the default states with Invoice::getDefaultStates(), which returns a collection of state classes, keyed by column:

```
["state"=>'App\States\Invoice\Pending',"fulfillment"=>null,
]
```

You can also get the default state for a specific column with Invoice::getDefaultStateFor('state'), which returns:

```
'App\States\Invoice\Pending'
```

Serializing states

Configuring transitions

Help us improve this page

### On this page

- Get Registered States
- Get Default States

Ray

Debug your applications faster

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