
import React, { useEffect, useState} from 'react';
import './App.scss';

function App() {
  // const today = new Date();
  // const currentMonth = today.getMonth();
  // const currentYear = today.getFullYear();
  // const currentDay = today.getDate();
  // const currentDayOfWeek = today.getDay();
  const [selectedDay, setSelectedDay] = useState(null);
  const [selectedMonth, setSelectedMonth] = useState(null);
  const [selectedYear, setSelectedYear] = useState(null);
  const [selectedDate, setSelectedDate] = useState(null);
  const Months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

  useEffect(() => {
    const today = new Date();
    setSelectedDay(formatDate(today));
    setSelectedMonth(today.getMonth());
    setSelectedYear(today.getFullYear());
  }, []);

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
    return new Date(year, month, 1).getDay();
  }

  function isToday(day) {
    return false;
  }


  return (
    <section className="vz-time-slot-selection">
      <header>
        <h1>{Months[selectedMonth]} {selectedYear}</h1>
        <h2>{selectedDay}</h2>
        
        <button onClick={(e) => previousMonth(e) }>
          Previous Month
        </button>
        <button onClick={(e) => nextMonth(e) }>
          Next Month
        </button>
      </header>
      <div className="calendar">
        <table>
          <div>
            <div className="month">
              {days.map((day, index) => (
                <div className="day --header" key={index}>{day}</div>
              ))}
              {
                Array(getFirstDayOfMonth(selectedMonth + 1, selectedYear)).fill(null).map((_, index) => (
                  <div className="day --fill" key={index}></div>
                ))
              }
              {
                Array(getDaysInMonth(selectedMonth + 1, selectedYear)).fill(null).map((_, index) => (
                  <div className={`day --monthday ` + 
                    (isCurrentDate(index + 1) ? '--selected' : '')
                    +
                    (isToday(index + 1) ? '--istoday' : '')
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
        </table>
      </div>
    </section>
  );
}

export default App;
