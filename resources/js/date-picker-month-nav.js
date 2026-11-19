import dayjs from 'dayjs/esm'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import localeData from 'dayjs/plugin/localeData'
import timezone from 'dayjs/plugin/timezone'
import utc from 'dayjs/plugin/utc'
import dateTimePickerFormComponent from '../../vendor/filament/forms/resources/js/components/date-time-picker.js'

dayjs.extend(advancedFormat)
dayjs.extend(customParseFormat)
dayjs.extend(localeData)
dayjs.extend(timezone)
dayjs.extend(utc)

document.addEventListener('alpine:init', () => {
    Alpine.data('dateTimePickerFormComponentWithMonthNav', (config) => {
        const component = dateTimePickerFormComponent(config)

        return {
            ...component,

            focusPreviousMonth() {
                if (! this.focusedDate) {
                    this.focusedDate = dayjs().tz(dayjs.tz.guess())
                }

                this.focusedDate = this.focusedDate.subtract(1, 'month')
            },

            focusNextMonth() {
                if (! this.focusedDate) {
                    this.focusedDate = dayjs().tz(dayjs.tz.guess())
                }

                this.focusedDate = this.focusedDate.add(1, 'month')
            },
        }
    })
})
