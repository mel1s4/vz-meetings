import './App.scss';
import React, { use, useState, useEffect } from 'react';

function App() {
  const [timeZone, setTimeZone] = useState('');
  const availabilityRuleTemplate = {
    id: 0, 
    name: 'New Rule',
    type: 'between-dates',
    action: 'available',
    includeTime: false,
    startTime: '00:00',
    endTime: '23:59',
    weekdays: [],
    specificDate: '',
    startDate: '',
    endDate: '',
    showWeekdays: false,
  };
  const [availabilityRules, setAvailabilityRules] = useState([]);
  const weekdaysTemplate = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const ruleTypes = [
    {
      name: "On a Week Day",
      value: "week-day",
    },
    {
      name: "On a Specific Date",
      value: "specific-date",
    },
    {
      name: "Between Two Dates",
      value: "between-dates",
    },
  ];

  function moveRule(e, direction, id) {
    e.preventDefault();
    const ruleIndex = availabilityRules.findIndex((rule) => rule.id === id);
    const newAvailabilityRules = [...availabilityRules];
    const rule = newAvailabilityRules.splice(ruleIndex, 1)[0];
    switch (direction) {
      case 'up':
        if (ruleIndex === 0) {
          return;
        }
        newAvailabilityRules.splice(ruleIndex - 1, 0, rule);
        setAvailabilityRules(newAvailabilityRules);
        break;
      case 'down':
        if (ruleIndex === availabilityRules.length - 1) {
          return;
        }
        newAvailabilityRules.splice(ruleIndex + (direction === 'up' ? -1 : 1), 0, rule);
        setAvailabilityRules(newAvailabilityRules);
        break;
      case 'delete':
        setAvailabilityRules(availabilityRules.filter((rule) => rule.id !== id));
        break;
    }  
  }

  function saveAvailabilityRule(e, ruleId) {
    const ruleIndex = availabilityRules.findIndex((rule) => rule.id === ruleId);
    const newAvailabilityRules = [...availabilityRules];
    const rule = newAvailabilityRules[ruleIndex];
    switch (e.target.name) {
      case 'weekdays':
        const dayIndex = weekdaysTemplate.findIndex((day) => day === e.target.value); 
        if (e.target.checked) {
          rule.weekdays.push(dayIndex);
        } else {
          rule.weekdays = rule.weekdays.filter((day) => day !== dayIndex);
        }
        break;
      case 'start-time':
        rule.startTime = e.target.value;
        break;
      case 'end-time':
        rule.endTime = e.target.value;
        break;
      case 'rule-type':
        rule.type = e.target.value;
        break;
      case 'include-time':
        rule.includeTime = e.target.checked;
        break;
      case 'vz-rule-action':
        if (e.target.checked) {
          rule.action = 'available';
        }
        else {
          rule.action = 'unavailable';
        }
        break;
      case 'specific-date':
        rule.specificDate = e.target.value;
        break;
      case 'start-date':
        rule.startDate = e.target.value;
        break;
      case 'end-date':
        rule.endDate = e.target.value;
        break;
      case 'show-weekdays':
        rule.showWeekdays = e.target.checked;
        break;
    }
    setAvailabilityRules(newAvailabilityRules);
  }

  function addRule(e) {
    e.preventDefault();
    const nAr = {...availabilityRuleTemplate, id: availabilityRules.length + 1};
    setAvailabilityRules([
      nAr,
      ...availabilityRules 
    ]);
  }

  function valuesAsJson() {
    return JSON.stringify(availabilityRules);
  }

  useEffect(() => {
    if (window?.vz_availability_rules_params) {
      setAvailabilityRules(window.vz_availability_rules_params.availability_rules);
      setTimeZone(window.vz_availability_rules_params.time_zone);
    }
  }
  , []);
  return (
      <section className="vz-availability-rules">  
        <button className="add-rule" onClick={(e) => addRule(e)}>
          + New Rule
        </button>
        <div className="vz-availability-rules__container">
          <ul className="vz-availability-rules__list">
            {availabilityRules.length > 0 && availabilityRules.map((rule) => {
                return (
                  <li key={rule.id} className="vz-availability-rule__item">
                     <section className="vz-rule-nav">
                        <button onClick={(e) => moveRule(e,'up', rule.id)}>
                          Up
                        </button>
                        <button onClick={(e) => moveRule(e,'down', rule.id)}>
                          Down
                        </button>
                        <button onClick={(e) => moveRule(e,'delete', rule.id)}>
                          Delete
                        </button>
                      </section>
                    <form onChange={(e) => saveAvailabilityRule(e, rule.id)} 
                          className="vz-availability-rule">
                      <b>
                        #{ rule.id }
                      </b>
                      <div className="vz-am__ar__input">
                        <div className={`availability-toggle ${rule.action === 'available' ? '--available' : '--unavailable'}`}>
                          <label>
                            Available
                            <input type="checkbox" 
                                  name="vz-rule-action" 
                                    value="available"
                                    {
                                      ...(rule.action === 'available' ? {defaultChecked: true} : {})
                                    } />
                          </label>
                          <span> { rule.action === 'available' ? 'Add' : 'Subtract' } </span>
                        </div>
                      </div>
                      <div className="vz-am__ar__input --type">
                        <label>
                          Type
                        </label>
                        <select name="rule-type"
                                defaultValue={rule.type}>
                          {
                            ruleTypes.map((type) => {
                              return (
                                <option key={type.value} 
                                        value={type.value}>
                                  {type.name}
                                </option>
                              );
                            })
                          }
                        </select>
                      </div>
                      <div className="vz-am__ar__input">
                        <label>
                          <input type="checkbox" 
                                name="include-time"
                                value = "include-time"
                                {
                                  ...(rule.includeTime ? {checked: true} : {})
                                }
                                />
                          Include Time
                        </label>
                      </div>
                      {
                        rule.includeTime && (
                          <div  className="vz-am__ar__input --time-range">
                            <p>
                              Select Time Range
                            </p>
                            <label>
                              <span>Start Time </span>
                              <input type="time"
                                    name="start-time"
                                    defaultValue={rule.startTime} />
                            </label>
                            <label>
                              <span>End Time </span>
                              <input type="time"
                                    name="end-time"
                                    defaultValue={rule.endTime} />
                            </label>
                          </div>
                        )
                      }
                      {
                        rule.type === 'between-dates' &&
                          (
                            <div className="vz-am__ar__input">
                              <label>
                                <input type="checkbox"
                                      name="show-weekdays"
                                      value="show-weekdays"
                                      {
                                        ...(rule.showWeekdays ? {defaultChecked: true} : {})
                                      } />
                                On Weekdays
                              </label>
                            </div>
                          )
                      }
                      {
                        rule.type === 'between-dates' && (
                          <div className="vz-am__ar__input --time-range">
                            <label>
                            <span> Start Date </span>
                              <input type="date"
                                     name="start-date"
                                      defaultValue={rule.startDate} />
                            </label>
                            <label>
                              <span> End Date </span>
                              <input type="date"
                                     name="end-date"
                                      defaultValue={rule.endDate} />
                            </label>
                          </div>
                        )
                      }
                      {
                        ((rule.type === 'between-dates' && rule.showWeekdays) ) && (
                          <div className="vz-am__ar__input --weekdays">
                            <p>
                              Select Week Days
                            </p>
                            <ul className="weekdays-selection">
                            {
                              weekdaysTemplate.map((day, index) => {
                                return (
                                  <li>
                                    <label key={day} className={ rule.weekdays.includes(index) ? '--selected' : '' }>
                                      <input type="checkbox" 
                                              name="weekdays"
                                             value={day}
                                            {
                                                ...(rule.weekdays.includes(index) ? {defaultChecked: true} : {})
                                            }
                                            />
                                        {day}
                                    </label>
                                  </li>
                                );
                              })
                            }
                            </ul>
                          </div>
                        )
                      }
                      {
                        rule.type === 'specific-date' && (
                          <div  className="vz-am__ar__input --time-range">
                            <label>
                              <span> Select Date </span>
                              <input type="date"
                                     name="specific-date"
                                      defaultValue={rule.specificDate} />
                            </label>
                          </div>
                        )
                      }
                      
                     
                      
                    </form>
                  </li>
                );
              })}
          </ul>
        </div>
        
        <input type="hidden"
                value={valuesAsJson()}
                name="vz-appointments-availability-rules"
                 />
    </section>
  );
}

export default App;