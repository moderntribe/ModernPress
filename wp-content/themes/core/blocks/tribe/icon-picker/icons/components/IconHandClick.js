import * as React from 'react';
const SvgIconHandClick = ( props ) => (
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
			d="M24 38v6M48.255 34.956 32 32V20a4 4 0 0 0-8 0v18h-4a4 4 0 0 0-4 4v5.79a12 12 0 0 0 2.63 7.497L24 62h26l3.112-20.229a6 6 0 0 0-4.857-6.815ZM28 2v4M40.728 7.272 37.9 10.101M46 20h-4M15.272 7.272l2.829 2.829M10 20h4"
		/>
	</svg>
);
export default SvgIconHandClick;
