import * as React from 'react';
const SvgIconBuildingHome = ( props ) => (
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
			d="M2 28 32 4l30 24M26 60V44h12v16"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M10 32v22a6 6 0 0 0 6 6h32a6 6 0 0 0 6-6V32"
		/>
	</svg>
);
export default SvgIconBuildingHome;
