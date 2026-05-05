import * as React from 'react';
const SvgIconPresentation = ( props ) => (
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
			d="M22 22v40M14 14a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM42 22H10a4 4 0 0 0-4 4v36"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M28 8h30v36H30"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m44 44 10 18"
		/>
	</svg>
);
export default SvgIconPresentation;
