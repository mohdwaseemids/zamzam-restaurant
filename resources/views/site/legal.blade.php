@extends('layouts.site')

@section('title', $title.' — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('header-class', 'bg-ink')

@section('content')
@php
    $name = setting('site_name', 'Zam Zam Family Restaurant');
    $address = setting('address', 'Kairana, Uttar Pradesh');
    $phone = setting('phone');
    $email = setting('email');
    $contact = collect([$phone ? 'phone '.$phone : null, $email ? 'email '.$email : null])->filter()->implode(' or ');
    $contact = $contact ?: 'the phone number shown on our Contact page';
@endphp

<section class="wrap max-w-3xl pb-24 pt-36">
    <p class="eyebrow">Legal</p>
    <h1 class="display mt-4 text-3xl text-cream md:text-5xl">{{ $title }}</h1>
    <div class="ornament mt-6"><span></span></div>

    <div class="legal mt-10 space-y-5 text-lg leading-relaxed text-cream/85 [&_a]:text-gold [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-xl [&_h2]:tracking-widest [&_h2]:text-gold-light [&_li]:ml-5 [&_li]:list-disc [&_ul]:space-y-2">
        @if ($customHtml)
            {!! $customHtml !!}
        @elseif ($page === 'privacy-policy')
            <p>{{ $name }} ("we", "us") runs this website to take food orders and table bookings. This page explains what information we collect and how we use it.</p>

            <h2>What we collect</h2>
            <ul>
                <li>Your name, mobile number, and email address if you give one.</li>
                <li>Your delivery address and any saved addresses on your account.</li>
                <li>Details of your orders, table bookings, reviews and messages to us.</li>
                <li>Basic technical information such as your browser type, used to keep the website working and secure.</li>
            </ul>

            <h2>How we use it</h2>
            <ul>
                <li>To prepare and deliver your order, confirm table bookings and contact you about them.</li>
                <li>To keep your order history, saved addresses and loyalty points in your account.</li>
                <li>To answer your questions and improve our food and service.</li>
            </ul>

            <h2>Payments</h2>
            <p>Online payments are handled by Razorpay. We never see or store your card number, UPI PIN or bank password. Razorpay shares only the payment status and a payment reference with us.</p>

            <h2>Sharing</h2>
            <p>We do not sell your information. We share it only with those who need it to complete your order — our delivery staff, our payment provider — or when the law requires us to.</p>

            <h2>Cookies</h2>
            <p>We use essential cookies to keep you signed in and to remember your cart. We do not use advertising cookies.</p>

            <h2>Your choices</h2>
            <p>You can see and change your details from My account. To have your account and personal details removed, contact us on {{ $contact }}. We may keep order and invoice records for as long as tax law requires.</p>

            <h2>Contact</h2>
            <p>{{ $name }}, {{ $address }}. Reach us on {{ $contact }}.</p>
        @elseif ($page === 'terms')
            <p>These terms apply when you use this website to order food or book a table with {{ $name }}, {{ $address }}.</p>

            <h2>Orders</h2>
            <ul>
                <li>An order is accepted once we confirm it. We may decline an order if a dish is sold out, the address is outside our delivery area, or we are closed.</li>
                <li>Please give a correct mobile number and address. We may call you to confirm the order.</li>
                <li>Delivery times are estimates and can change with traffic, weather and how busy the kitchen is.</li>
            </ul>

            <h2>Prices and payment</h2>
            <ul>
                <li>Prices are in Indian Rupees. Delivery charges and taxes, where they apply, are shown before you place the order.</li>
                <li>You can pay online through Razorpay or in cash, where offered.</li>
                <li>Coupons and loyalty points have no cash value, cannot be combined unless we say so, and may be withdrawn if misused.</li>
            </ul>

            <h2>Food and allergies</h2>
            <p>Our kitchen handles meat, fish, dairy, nuts, gluten and spices. If you have an allergy, please tell us before ordering. Photos are for illustration; presentation may differ.</p>

            <h2>Table bookings</h2>
            <p>A booking request is confirmed only after we call or message you. Please arrive on time; we may release the table after 15 minutes.</p>

            <h2>Your account</h2>
            <p>Keep your password private. You are responsible for orders placed from your account.</p>

            <h2>Reviews</h2>
            <p>Reviews should be honest and respectful. We may choose not to publish a review.</p>

            <h2>Law</h2>
            <p>These terms are governed by the laws of India. Courts in Uttar Pradesh have jurisdiction.</p>

            <h2>Contact</h2>
            <p>Questions about these terms: {{ $contact }}.</p>
        @else
            <p>We want you to enjoy every order from {{ $name }}. This is how cancellations and refunds work.</p>

            <h2>Cancelling an order</h2>
            <ul>
                <li>You can cancel free of charge before we start preparing your food. Call us straight away on {{ $phone ?: 'the number on our Contact page' }}.</li>
                <li>Once the food is being prepared or is out for delivery, the order cannot be cancelled.</li>
            </ul>

            <h2>When we refund</h2>
            <ul>
                <li>We could not deliver your order, or we cancelled it.</li>
                <li>A dish was missing, wrong, or not fit to eat. Please tell us within 2 hours of receiving it, with a photo if possible.</li>
                <li>You were charged more than once for the same order.</li>
            </ul>

            <h2>How refunds are paid</h2>
            <ul>
                <li>Online payments are refunded to the same card, UPI or bank account through Razorpay, normally within 5 to 7 working days.</li>
                <li>Cash orders are refunded in cash or by UPI.</li>
                <li>Loyalty points used on a cancelled order are returned to your account.</li>
            </ul>

            <h2>When we cannot refund</h2>
            <ul>
                <li>The address or phone number given was wrong, or nobody was available to receive the order.</li>
                <li>A change of mind after the food has been prepared.</li>
            </ul>

            <h2>Contact</h2>
            <p>For any cancellation or refund, reach us on {{ $contact }}. {{ $name }}, {{ $address }}.</p>
        @endif
    </div>

    <p class="mt-12 border-t border-gold/15 pt-6 text-sm text-sand">Last updated {{ now()->format('F Y') }}.</p>
</section>
@endsection
