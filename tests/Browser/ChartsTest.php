<?php

declare(strict_types=1);

it('has no accessibility issues with the charts on the page', function (): void {
    visit('/')->assertNoJavaScriptErrors()->assertNoAccessibilityIssues();
});

it('has no accessibility issues with the charts in dark mode', function (): void {
    $page = visit('/');
    $page->script('document.documentElement.classList.add("dark"); document.body.replaceChildren(document.querySelector("[aria-labelledby=charts-demo-heading]"))');

    $page->assertNoJavaScriptErrors()->assertNoAccessibilityIssues();
});

it('keeps the chart data table hidden until the toggle opens it', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelector("#chart-bars-table").getBoundingClientRect().width <= 1')
        ->assertScript('document.querySelector("#chart-bars button[aria-controls]").getAttribute("aria-expanded") === "false"')
        ->click('#chart-bars button[aria-controls]')
        ->assertScript('document.querySelector("#chart-bars button[aria-controls]").getAttribute("aria-expanded") === "true"')
        ->assertScript('document.querySelector("#chart-bars-table").getBoundingClientRect().width > 100')
        ->assertScript('document.querySelector("#chart-bars button[aria-controls]").textContent.trim().startsWith("Hide data table")')
        ->assertNoAccessibilityIssues()
        ->click('#chart-bars button[aria-controls]')
        ->assertScript('document.querySelector("#chart-bars-table").getBoundingClientRect().width <= 1');
});

it('draws the charts with escaped labels', function (): void {
    visit('/')->assertSee('Event <b>')
        ->assertScript('document.querySelectorAll("#chart-donut b").length === 0')
        ->assertScript('document.querySelectorAll("#chart-bars svg rect").length === 8')
        ->assertScript('document.querySelectorAll("#chart-empty svg").length === 0');
});
