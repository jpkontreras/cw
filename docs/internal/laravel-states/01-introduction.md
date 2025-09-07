# Laravel Model States

## 

Adding state behaviour to Eloquent models

Repository

4,689,239

1,232

## Introduction

### On this page

1. We have badges!

This package adds state support to models. It combines concepts from the state pattern and state machines.

It is recommended that you're familiar with both patterns if you're going to use this package.

To give you a feel about how this package can be used, let's look at a quick example.

Imagine a model Payment, which has three possible states: Pending, Paid and Failed. This package allows you to represent each state as a separate class, handles serialization of states to the database behind the scenes, and allows for easy and controller state transitions.

For the sake of our example, let's say that depending on the state the color of a payment should differ.

Here's what the Payment model would look like:

```
useSpatie\ModelStates\HasStates;classPaymentextendsModel{useHasStates;protected$casts= ['state'=>PaymentState::class,
    ];
}
```

This is what the abstract PaymentState class would look like:

```
useSpatie\ModelStates\State;useSpatie\ModelStates\StateConfig;abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class,Paid::class)
            ->allowTransition(Pending::class,Failed::class)
        ;
    }
}
```

Here's a concrete implementation of one state, the Paid state:

```
classPaidextendsPaymentState{publicfunctioncolor():string{return'green';
    }
}
```

And here's how it's used:

```
$payment=Payment::find(1);$payment->state->transitionTo(Paid::class);echo$payment->state->color();
```

There's a lot more to tell about how this package can be used. So let's dive in.

## # # We have badges!

About us

Postcardware

Help us improve this page

### On this page

- We have badges!

Mailcoach

Check out our full-featured (self-hosted) email marketing solution

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