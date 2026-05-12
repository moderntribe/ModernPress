import * as React from 'react';
const SvgIconHourglassTime = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M16 60V48c0-10 10-16 10-16s-10-6-10-16V4M48 60V48c0-10-10-16-10-16s10-6 10-16V4"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M10 4h44M10 60h44"
		/>
	</svg>
);
export default SvgIconHourglassTime;
