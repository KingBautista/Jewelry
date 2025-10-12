import { forwardRef, useEffect, useState } from "react";
import axiosClient from "../axios-client";

const MediaDateDropdown = forwardRef((props, ref) => {
  const [isLoading, setIsLoading] = useState(false);
  const [dropdownList, setDropdownList] = useState(false);
  const [selectedOption, setSelectedOption] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  const getDateDirectories = () => {
    setIsLoading(true);
    axiosClient.get('/options/media/dates')
    .then(({data}) => {
      setDropdownList(data);
      setIsLoading(false);
    })
    .catch((errors) => {
      console.error('Error fetching date directories:', errors);
      setErrorMsg('Failed to load date options');
      setIsLoading(false);
    });
  };

  useEffect(() => {
    getDateDirectories();
  }, []);

  const renderOptions = () => {
    const options = Array.from(dropdownList).map(date => {
      return (
        <option key={date.value} value={date.value}>
          {date.label}
        </option>
      );
    });

    return options;
  };

  const options = renderOptions();

  return (
    <div>
      <select className="form-select" ref={ref}
      value={selectedOption} 
      onChange={ev => {ev.preventDefault(); setSelectedOption(ev.target.value); props.onChange(ev)}}>
        {isLoading && <option value="">Loading ...</option>}
        {!isLoading && <option value="">All dates</option>}
        {!isLoading && options}
      </select>
      {errorMsg && <small className="text-danger">{errorMsg}</small>}
    </div>
  );
});

export default MediaDateDropdown;