<script type="x/template" id="day-slider-template">
    <div class="clock-history-header">
        <div class="clock-history-header-left">
            <div class="clock-history-navigation">
                <button class="clock-history-previous" @click="subtractDay">&lt;
                    <span class="visuallyhidden">Afficher le jour précédent</span>
                </button>
            </div>
            <h2 class="clock-history-title with-calendar">@{{ currentDate }}</h2>
            <div class="clock-history-navigation">
                <button class="clock-history-next" @click="addDay">&gt;
                    <span class="visuallyhidden">Afficher le jour suivant</span>
                </button>
            </div>
        </div>
        <div class="clock-history-header-right">
            <div class="time-total-wrapper">
                <span class="time-total-label">
                    <span>Temps total</span>
                    <span class="icon-wrapper time-wrapper">
                        <svg width="20" height="20" class="time">
                            <use xlink:href="/svg/symbols.svg#time"></use>
                        </svg>
                    </span>
                </span>
                <span class="time-total">@{{ workedTimeToday|minutesToHours }}</span>
            </div>
        </div>
    </div>
</script>
