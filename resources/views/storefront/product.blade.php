@extends('layouts.app')

@section('title', $meta['title'])

@push('head')
    <meta name="description" content="{{ $meta['description'] }}">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta property="og:image" content="{{ $meta['image'] }}">
@endpush

@section('content')
    <livewire:storefront.product :product="$product" :related-products="$relatedProducts" :currency="$currency" />
@endsection
