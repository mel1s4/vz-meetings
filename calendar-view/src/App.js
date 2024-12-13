
import React, { use, useEffect, useState} from 'react';
import axios from 'axios';
import './App.scss';

function App() {
  // const today = new Date();
  // const currentMonth = today.getMonth();
  // const currentYear = today.getFullYear();
  // const currentDay = today.getDate();
  // const currentDayOfWeek = today.getDay();
  const [timeSlotSize, setTimeSlotSize] = useState(30);
  const [calendarId, setCalendarId] = useState(null);
  const [selectedDay, setSelectedDay] = useState(null);
  const [selectedMonth, setSelectedMonth] = useState(null);
  const [selectedYear, setSelectedYear] = useState(null);
  const Months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  const [monthAvailability, setMonthAvailability] = useState([]);
  const [timeSlots, setTimeSlots] = useState({});
  const [timeZone, setTimeZone] = useState(null);
  const [restUrl, setRestUrl] = useState('http://localhost/wp-json/');
  const [selectedTimeSlot, setSelectedTimeSlot] = useState(null);
  useEffect(() => {
    const today = new Date();
    // setCalendarId(window?.calendar_params?.calendar_id);
    setCalendarId(19);
    setSelectedDay(formatDate(today));
    setSelectedMonth(today.getMonth());
    setSelectedYear(today.getFullYear());
    const exampleTimeSlots = {
      '11:00': true,
      '11:48': true,
      '12:36': true,
      '13:24': true,
      '14:12': true,
      '15:00': true,
      '15:48': true,
      '16:36': true,
    };
    setTimeSlots(exampleTimeSlots);
    console.log(window?.vz_calendar_view_params);

    if (window?.vz_calendar_view_params) {
      const { calendar_id, rest_url, time_zone, availability, slot_size } = window.vz_calendar_view_params;
      setCalendarId(calendar_id);
      setRestUrl(rest_url);
      setTimeZone(time_zone);
      setTimeSlotSize(slot_size);

      const nmA = {};
      nmA[today.getFullYear() + '-' + (today.getMonth() + 1)] = availability?.available_days;
      setMonthAvailability(nmA);
      setTimeSlots(availability?.timeslots);

    }
  }, []);

  function selectedDateFormatted() {
    if (selectedDay) {
      const [y, month, date] = selectedDay.split('-');
      return `${Months[parseInt(month) - 1].slice(0,3)} ${parseInt(date)}`;
    }
    return
  }

  
  useEffect(() => {
    if (selectedMonth >= 0 && selectedYear > 2000 && restUrl ) {
      getMonthAvailability(selectedMonth + 1, selectedYear);
    }
  }, [selectedMonth, selectedYear, restUrl]);

  useEffect(() => {
    if (selectedDay && calendarId) {
      getTimeSlots(selectedDay);
    }
  }
  , [selectedDay]);

  async function getTimeSlots(day) {
    try {
      const getParams = {
        month: selectedMonth + 1,
        year: selectedYear,
        day: day,
        calendar_id: calendarId
      };
      const response = await axios.get( restUrl + 'vz-am/v1/timeslots', { params: getParams });
      setTimeSlots(response.data);
    } catch (error) {
      console.error(error);
    }
  }

  async function getMonthAvailability() {
    if (monthAvailability[selectedYear + '-' + (selectedMonth + 1)]) {
      return;
    }
    try {
      const getParams = {
        year: selectedYear,
        month: selectedMonth + 1,
        calendar_id: calendarId
      };
      console.log(selectedYear);
      const response = await axios.get( restUrl + 'vz-am/v1/availability', { params: getParams });
      const newMonthAvailability = { ...monthAvailability };
      newMonthAvailability[selectedYear + '-' + (selectedMonth + 1)] = response.data.available_days;
      setMonthAvailability(newMonthAvailability);
      console.log(newMonthAvailability);
    } catch (error) {
      console.error(error);
    }
  }

  function isCurrentDate(day) {
    if (selectedDay) {
      const [year, month, date] = selectedDay.split('-');
      return parseInt(date) === day && parseInt(month) === selectedMonth + 1 && parseInt(year) === selectedYear;
    }
    return false
  }

  function formatDate(date = new Date()) {
    const year = date.toLocaleString('default', { year: 'numeric' });
    const month = date.toLocaleString('default', { month: '2-digit' });
    const day = date.toLocaleString('default', { day: '2-digit' });
    return [year, month, day].join('-');
  }

  function nextMonth (e) {
    e.preventDefault();
    if (selectedMonth === 11) {
      setSelectedYear(selectedYear + 1);
      setSelectedMonth(0);
      return;
    }
    setSelectedMonth(selectedMonth + 1);
  }

  function previousMonth (e) {
    e.preventDefault();
    if (selectedMonth === 0) {
      setSelectedYear(selectedYear - 1);
      setSelectedMonth(11);
      return;
    }
    setSelectedMonth(selectedMonth - 1);
  }

  function getDaysInMonth(month, year) {
    return new Date(year, month, 0).getDate();
  }

  function getFirstDayOfMonth(month, year) {
    
    console.log(month, year, new Date(year, month, 1).getDay());
    return new Date(year, month, 1).getDay();
  }

  function selectTimeSlot(e, index) {
    e.preventDefault();
    setSelectedTimeSlot(index);
    console.log(timeSlots[index]);
  }

  function isToday(day) {
    const today = new Date();
    const [year, month, date] = formatDate(today).split('-');
    return parseInt(date) === day && parseInt(month) === selectedMonth + 1 && parseInt(year) === selectedYear;
    return false;
  }

  function isAvailable(day) {
    if (!monthAvailability[selectedYear + '-' + (selectedMonth + 1)]) {
      return false;
    }
    return monthAvailability[selectedYear + '-' + (selectedMonth + 1)][day];
  }

  return (
    <section className="vz-time-slot-selection">
      <div className="vz-availability-calendar">
        <header>
          <h1 className="title">{Months[selectedMonth]} {selectedYear}</h1>
          <button className="vz-calendar-nav__button --prev" onClick={(e) => previousMonth(e) }>
            Previous
          </button>
          <button className="vz-calendar-nav__button --next" onClick={(e) => nextMonth(e) }>
            Next
          </button>
        </header>
        <div className="month">
          {days.map((day, index) => (
            <div className="day --header" key={index}>{day}</div>
          ))}
          {
            Array(getFirstDayOfMonth(selectedMonth, selectedYear)).fill(null).map((_, index) => (
              <div className="day --fill" key={index}></div>
            ))
          }
          {
            Array(getDaysInMonth(selectedMonth, selectedYear)).fill(null).map((_, index) => (
              <div className={`day --monthday ` + 
                (isCurrentDate(index + 1) ? ' --selected' : '') +
                (isToday(index + 1) ? ' --istoday' : '') +
                (isAvailable(index + 1) ? ' --available' : ' --unavailable')
              } key={index}>
                <button
                  onClick={() => setSelectedDay(formatDate(new Date(selectedYear, selectedMonth, index + 1)))}
                  className="day-button"
                >
                  {index + 1}
                </button>
              </div>
            ))
          }
        </div>
      </div>
      <div className="vz-time-slots">
        <h2 className="title">{selectedDateFormatted()}</h2>
        <ul className="vz-am__time-slots__list">
            {
              Object.keys(timeSlots).map((timeSlot, index) => (
                <li key={index} className="vz-am__time-slots__item">
                  <button
                    onClick={(e) => selectTimeSlot(e, index)}
                    className={`vz-am__time-slot__button ${index === selectedTimeSlot ? '--selected' : ''}`}
                  >
                    {timeSlot}
                  </button>
                </li>
              ))
            }
        </ul>
      </div>
    </section>
  );
}

export default App;
