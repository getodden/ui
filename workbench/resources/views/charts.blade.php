<section aria-labelledby="charts-demo-heading">
    <h2 id="charts-demo-heading">Charts</h2>

    <x-ui::stat id="stat-revenue" label="New contacts" value="1,248" delta="+12%" delta-label="vs last month" :sparkline="[3, 5, 4, 8, 7, 11, 12]" />
    <x-ui::stat id="stat-churn" label="Churn" value="2.1%" delta="-0.4%" sentiment="positive" />
    <x-ui::stat id="stat-flat" label="Open tickets" value="14" delta="0" />

    <x-ui::bar-chart id="chart-bars" label="Deals by month" label-visible :categories="['Jan', 'Feb', 'Mar', 'Apr']" :series="[['name' => 'Won', 'values' => [4, 7, 5, 9]], ['name' => 'Lost', 'values' => [2, 3, 1, 4]]]" />
    <x-ui::bar-chart id="chart-bars-horizontal" label="Pipeline by owner" :categories="['Ada Lovelace', 'Grace Hopper', 'Alan Turing']" :series="[['name' => 'Open', 'values' => [12, 8, 15]]]" orientation="horizontal" />
    <x-ui::bar-chart id="chart-bars-stacked" label="Tickets by status" :categories="['Mon', 'Tue', 'Wed']" :series="[['name' => 'Open', 'values' => [5, 3, 6]], ['name' => 'Closed', 'values' => [2, 6, 4]]]" stacked />
    <x-ui::line-chart id="chart-lines" label="Signups per week" :categories="['W1', 'W2', 'W3', 'W4', 'W5']" :series="[['name' => 'Organic', 'values' => [10, 14, 12, 18, 21]], ['name' => 'Referral', 'values' => [4, 6, null, 9, 11]]]" />
    <x-ui::line-chart id="chart-line-single" label="One reading" :categories="['Today']" :series="[['name' => 'Score', 'values' => [42]]]" />
    <x-ui::donut-chart id="chart-donut" label="Contacts by source" :segments="[['label' => 'Web', 'value' => 40], ['label' => 'Referral', 'value' => 25], ['label' => 'Event <b>', 'value' => 10]]" />
    <x-ui::bar-chart id="chart-empty" label="Nothing yet" :categories="[]" :series="[]" />
</section>
