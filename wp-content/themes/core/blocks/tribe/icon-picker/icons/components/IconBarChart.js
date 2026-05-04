import * as React from 'react';
const SvgIconBarChart = ( props ) => (
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
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M4 58h56M18 26H6v24h12zM38 6H26v44h12zM58 36H46v14h12z"
		/>
	</svg>
);
export default SvgIconBarChart;
