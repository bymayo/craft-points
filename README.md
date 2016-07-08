<img src="https://raw.githubusercontent.com/madebyshape/points/master/screenshots/icon.png" width="50">

# Points

Points is a Craft CMS plugin that allows you create a points / scoring / credits system and assign these to a user.

Points are assigned to a user by firstly creating an 'Event' (E.g. Signed Up to Newsletter = 20pts) and then assigning this event to a particular user when they have performed that action.

## Features

- Assign points to a particular user
- Control Panel to manage events
- Control Panel to manage point entries
- Generate a 'Points Total' per user
- Craft 2.5 compatible

## Install

- Add the `points` directory into your `craft/plugins` directory.
- Navigate to Settings -> Plugins and click the "Install" button.

## Usage

Before you can assign points to a user, you firstly need to create an 'Event'. An event is essentially an action the user has completed, e.g. Logged In, Signed Up to Newsletter, Bought a Product etc. 

To do this, navigate to `Points -> Events` in the sidebar after installing and click 'Add Event' in the top right. Give the event a name and decide how many points the user receives once this event is triggered. 

If you want to allow this event to be triggered multiple times rather than once, switch the `Multiple` option on. If this is turned off then the event can only be assigned to a user once.

To then assign points to a user you can do this via the CMS `Points -> Entries` or using a Twig tag (See below)

## Templating

### Add

To add points to a user you only really need to specify an events `eventHandle`.

	{{ craft.points.add(
		{ 
			eventHandle: 'SignedUpToNewsletter'
		}
	) }}
	
This will assign the points from the `SignedUpToNewsletter` event to the current logged in user. If you want to add points to a user that is not currently logged in, just specify the `userId` parameter.
	
	{{ craft.points.add(
		{ 
			userId: 5
			eventHandle: 'SignedUpToNewsletter'
		}
	) }}
	
You can assign the `userId` dynamically by creating a variable and passing it to the add method. The below example takes the user from the segment 1 of the URL.

	{% set user = craft.user.username(craft.request.getSegment(1)).first() %}

	{{ craft.points.add(
		{ 
			userId: user.id
			eventHandle: 'SignedUpToNewsletter'
		}
	) }}

### Remove

You can also remove points from a user in the same way as adding points. Optionally adding in the `userId` parameter. It's important to remember that if you have selected 'Multiple' on the event, that removing points only removes 1 instance, not all.

	{{ craft.points.remove(
		{ 
			eventHandle: 'SignedUpToNewsletter'
		}
	) }}

### Total

To get a total amount of points a user has, use the `.total` option.

	{{ craft.points.total() }}
	
Again, the `userId` parameter is totally optional, but can be added in if need be.

	{{ craft.points.total(
		{ 
			userId: 5
		}
	) }}

## Widget

Points also comes with a widget to see the latest points assigned to users directly on the dashboard. 

## Roadmap

- Leaderboard
- More Widgets - Leaderboard Widget, Events Widget
- Delay - Set a delay multiple points can be assigned to a user

## Credits

- Diamond by Edward Boatman from the Noun Project (https://thenounproject.com/edward/)